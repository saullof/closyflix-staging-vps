<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstallerSaveAdminInfoRequest;
use App\Http\Requests\InstallerSaveDBInfoRequest;
use App\Providers\AuthServiceProvider;
use App\Providers\GenericHelperServiceProvider;
use App\Providers\InstallerServiceProvider;
use App\Providers\ListsHelperServiceProvider;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PDO;
use Illuminate\Support\Facades\Config;

class InstallerController extends Controller
{
    /**
     * Renders installer steps views.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function install(Request $request)
    {
        if (InstallerServiceProvider::checkIfInstalled()) {
            return Redirect::to(route('home'));
        }
        $step = $request->get('step') ? (int) $request->get('step') : 1;
        if ($step == 1) {
            $phpExtensions = InstallerServiceProvider::getRequiredExtensions();

            return view('installer.requirements', [
                'requiredExtensions' => $phpExtensions,
                'passesRequirements' => InstallerServiceProvider::passesRequirements(),
            ]);
        } elseif ($step == 2) {
            return view('installer.database');
        } elseif ($step == 3) {
            return view('installer.admin');
        }

        return Redirect::to(route('installer.install').'?step=1');
    }

    /**
     * Checks if db connections is valid, if so, it saves it into the session.
     *
     * @param InstallerSaveDBInfoRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function testAndSaveDBInfo(InstallerSaveDBInfoRequest $request)
    {
        if (InstallerServiceProvider::checkIfInstalled()) {
            return Redirect::to(route('home'));
        }

        $db_host = $request->get('db_host');
        $db_port = $request->get('db_port') ? $request->get('db_port') : config('database.connections.mysql.port');
        $db_name = $request->get('db_name');
        $db_username = $request->get('db_username');
        $db_password = $request->get('db_password');
        try {
            $dbCheck = new PDO("mysql:host={$db_host};port=".$db_port.";dbname={$db_name}", $db_username, $db_password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            session([
                'db_host' => $db_host,
                'db_port' => $db_port,
                'db_name' => $db_name,
                'db_username' => $db_username,
                'db_password' => $db_password,
            ]);
        } catch (\PDOException $ex) {
            return Redirect::to(route('installer.install').'?step=2')
                ->with('error', __('Database connection could not be established:').' '.$ex->getMessage());
        }

        return Redirect::to(route('installer.install').'?step=3'); // warning
    }

    /**
     * Starts the installation. Sets up .env and redirect to next step.
     *
     * @param InstallerSaveAdminInfoRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function beginInstall(InstallerSaveAdminInfoRequest $request)
    {
        if (InstallerServiceProvider::checkIfInstalled()) {
            return Redirect::to(route('home'));
        }

        if (!$request->session()->get('db_host')) {
            return Redirect::to(route('installer.install').'?step=3')
                ->with('error', __('Database connection could not be established. Go back to previous step.'));
        }

        $site_title = $request->get('site_title');
        $app_url = $request->get('app_url');
        $email = $request->get('email');
        $password = $request->get('password');
        $licenseCode = $request->get('license');

        $license = InstallerServiceProvider::gld($licenseCode);
        if (isset($license->error)) {
            return Redirect::to(route('installer.install').'?step=3')
                ->with('error', $license->error);
        }
        session(['license' => json_encode(array_merge((array)$license, ['code'=>$licenseCode]))]);
        session(['licenseCode' => $licenseCode]);
        if(!$this->saveEnvValues($request)){
            return Redirect::to(route('installer.install').'?step=3')
                ->with('error', InstallerServiceProvider::$acError);
        }

        return redirect()->route('installer.finishInstall')->with([
            'site_title' => $site_title,
            'email' => $email,
            'password' => $password,
            'app_url' => $app_url,
        ]);
    }

    /**
     * Doing the actual installation.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function finishInstall()
    {
        if (InstallerServiceProvider::checkIfInstalled()) {
            return Redirect::to(route('home'));
        }

        $site_title = session()->get('site_title');
        $app_url = session()->get('app_url');
        $email = session()->get('email');
        $password = session()->get('password');

        try {
            Schema::defaultStringLength(191);
            Artisan::call('migrate', ['--force'=>true]);
            Artisan::call('db:seed', ['--force'=>true]);
        } catch (\Exception $e) {
            throw new \Exception(__('Migrations or Seeds failed with following message').' "'.$e->getMessage().'"" '.__('Please re-create your database and try again. If error persists, please contact us.'));
        }

        // Forcing the code from steps below to use admin v2 for new installments
        Config::set('settings.admin_version', 'v2');
        Config::set('settings.repositories.database.table', 'settings');

        DB::statement("UPDATE settings SET `payload` = :title WHERE `name` = 'name' and `group` = 'site'", [
            'title' => json_encode($site_title),
        ]);

        DB::statement("UPDATE settings SET `payload` = :url WHERE `name` = 'app_url' and `group` = 'site'", [
            'url' => json_encode($app_url),
        ]);

        DB::statement("UPDATE settings SET `payload` = :val WHERE `name` = 'product_license_key' and `group` = 'license'", [
            'val' => json_encode(session()->get('licenseCode')),
        ]);

        // Default email settings, otherwise log fails as well on L12 onwards
        // Derive no-reply@<host> from $app_url, default to qdev.tech if invalid
        $host = parse_url($app_url, PHP_URL_HOST) ?: parse_url("http://{$app_url}", PHP_URL_HOST);
        $host = $host ? preg_replace('/^www\./i', '', strtolower($host)) : null;

        $from = ($host && !filter_var($host, FILTER_VALIDATE_IP) && $host !== 'localhost')
            ? "no-reply@{$host}"
            : "no-reply@qdev.tech";

        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $from = "no-reply@qdev.tech";
        }

        DB::table('settings')->updateOrInsert(
            ['group' => 'emails', 'name' => 'from_address'],
            ['payload' => json_encode($from)]
        );

        DB::table('settings')->updateOrInsert(
            ['group' => 'emails', 'name' => 'from_name'],
            ['payload' => json_encode(trim($site_title).' Admin')]
        );

        // 3. Add user & make it admin
        $user = AuthServiceProvider::createUser([
            'name' => 'Admin',
            'email' => $email,
            'password' => $password,
            'email_verified_at' => Carbon::now(),
        ]);
        Artisan::call("make-admin {$email}");
        GenericHelperServiceProvider::createUserWallet($user);
        ListsHelperServiceProvider::createUserDefaultLists($user->id);

        // 4. Create an installed file over public dir
        Storage::disk('local')->put('installed', session()->get('license'));

        // 5. Create storage symlink
        try {
            $exitCode = Artisan::call('storage:link');

            if ($exitCode !== 0) {
                logger()->warning('storage:link returned non-zero during install', [
                    'exit_code' => $exitCode,
                    'output' => Artisan::output(),
                ]);
            }
        } catch (\Throwable $e) {
            logger()->warning('storage:link threw during install', [
                'error' => $e->getMessage(),
            ]);
        }

        return Redirect::to($app_url);
    }

    /**
     * Renders upgrade view.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function upgrade()
    {
        if(!(Auth::check() && Auth::user()->role_id == 1)){
            return redirect(route('home'));
        }
        app()->instance('pretend_migration', true); // Register a flag
        $canMigrate = InstallerServiceProvider::hasAvailableMigrations();

        return view('installer.upgrade', ['canMigrate'=>$canMigrate]);
    }

    /**
     * Runs the update.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function doUpgrade()
    {
        try {
            Artisan::call('down');
            Artisan::call('migrate', ['--force'=>true]);
            Artisan::call('up');
        } catch (\Exception $e) {
            Redirect::to(route('installer.update'))->with('error', $e->getMessage());
        }

        return Redirect::to(route('installer.update'))->with('success', __('Database updated successfully.'));
    }

    /**
     * Saves db info to env file.
     * @param $request
     */
    public function saveEnvValues($request) {
        if(InstallerServiceProvider::getLockCode()){
            InstallerServiceProvider::appendToEnv('DB_HOST='.'"'.$request->session()->get('db_host').'"');
            InstallerServiceProvider::appendToEnv('DB_DATABASE='.'"'.$request->session()->get('db_name').'"');
            InstallerServiceProvider::appendToEnv('DB_USERNAME='.'"'.$request->session()->get('db_username').'"');
            InstallerServiceProvider::appendToEnv('DB_PORT='.'"'.$request->session()->get('db_port').'"');
            InstallerServiceProvider::appendToEnv('DB_PASSWORD='.'"'.$request->session()->get('db_password').'"');
            return true;
        }
        else{
            return false;
        }
    }
}
