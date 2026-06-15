<?php

namespace App\Observers;

use App\Model\Attachment;
use App\Model\User;
use App\Model\UserVerify;
use App\Providers\EmailsServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserVerifyObserver
{
    /**
     * Listen to the User updating event.
     *
     * @param  UserVerify  $userVerify
     * @return void
     */
    public function saving(UserVerify $userVerify)
    {
        if (
            $userVerify->getOriginal('status') === UserVerify::REQUESTED_STATUS &&
            $userVerify->status !== UserVerify::REQUESTED_STATUS
        ) {
            if ($userVerify->status === UserVerify::REJECTED_STATUS) {
                $emailSubject = __('Your identity check failed.');
                $button = [
                    'text' => __('Try again'),
                    'url' => route('my.settings', ['type' => 'verify']),
                ];
            } elseif ($userVerify->status === UserVerify::APPROVED_STATUS) {
                $emailSubject = __('Your identity check passed.');
                $button = [
                    'text' => __('Create a post'),
                    'url' => route('posts.create'),
                ];
            } else {
                return;
            }

            $user = User::find($userVerify->user_id);

            try {
                App::setLocale($user->settings['locale'] ?? 'en');
            } catch (\Exception $e) {
                App::setLocale('en');
            }

            EmailsServiceProvider::sendGenericEmail([
                'email' => $user->email,
                'subject' => $emailSubject,
                'title' => __('Hello, :name,', ['name' => $user->name]),
                'content' => __('Email identity checked', [
                    'siteName' => getSetting('site.name'),
                    'status' => __($userVerify->status),
                ]),
                'button' => $button,
            ]);
        }
    }

    public function deleting(UserVerify $userVerify): void
    {
        $this->deleteVerificationFiles($userVerify->user_id, $this->decodeFiles($userVerify->files));
    }

    private function decodeFiles(?string $files): array
    {
        if (!$files) {
            return [];
        }

        $decoded = json_decode($files, true);

        return is_array($decoded) ? array_values(array_filter($decoded)) : [];
    }

    private function deleteVerificationFiles(int $userId, array $files): void
    {
        if (empty($files)) {
            return;
        }

        $storage = Storage::disk(config('filesystems.defaultFilesystemDriver'));
        $attachments = Attachment::query()
            ->where('user_id', $userId)
            ->whereIn('filename', $files)
            ->get()
            ->keyBy('filename');

        foreach ($files as $file) {
            try {
                $attachment = $attachments->get($file);

                if ($attachment) {
                    $attachment->delete();
                    continue;
                }

                if ($storage->exists($file)) {
                    $storage->delete($file);
                }
            } catch (\Exception $exception) {
                Log::error("Failed deleting verification file: {$file}, e: ".$exception->getMessage());
            }
        }
    }
}
