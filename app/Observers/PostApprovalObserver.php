<?php

namespace App\Observers;

use App\Model\Post;
use App\Providers\EmailsServiceProvider;
use App\Model\User;
use Illuminate\Support\Facades\App;

class PostApprovalObserver
{
    /**
     * Listen to the User updating event.
     *
     * @param  Post  $post
     * @return void
     */
    public function saving(Post $post)
    {

    }

    public function updating(Post $post)
    {
        if($post->getOriginal('status') !== $post->status){
            // Sending out the user notification
            $user = User::find($post->user_id);
            try{
                App::setLocale($user->settings['locale']);
            }
            catch (\Exception $e){
                App::setLocale('en');
            }
            EmailsServiceProvider::sendGenericEmail(
                [
                    'email' => $user->email,
                    'subject' => __("Post status updated"),
                    'title' => __('Hello, :name,', ['name'=>$user->name]),
                    'content' => __('Your post has been :status.', ['status'=>Post::getStatusName($post->status)]),
                    'button' => [
                        'text' => __('View post'),
                        'url' => route('posts.get', ['post_id'=>$post->id, 'username'=>$user->username]),
                    ],
                ]
            );
        }

    }
}
