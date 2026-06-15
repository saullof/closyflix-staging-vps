@if(!Auth::user()->email_verified_at) @include('elements.resend-verification-email-box') @endif

<form method="POST" action="{{route('my.settings.profile.save',['type'=>'profile'])}}">
    @csrf
    @include('elements.dropzone-dummy-element')
    <div class="mb-4">
        <div class="">
            <div class="card profile-cover-bg">
                <img class="card-img-top centered-and-cropped" src="{{Auth::user()->cover}}">

                <div class="profile-asset-loading d-none">
                    <div class="shimmer"></div>
                </div>

                <div class="card-img-overlay d-flex justify-content-center align-items-center">
                    <div class="actions-holder d-none">

                        <div class="d-flex">
                            @if(getSetting('ai.images_enabled'))
                                <span class="h-pill h-pill-media h-pill-media--light pointer-cursor mr-1"
                                      onclick="ProfileSettings.generateUserAsset('cover')"
                                      data-toggle="tooltip" data-placement="top" title="{{__('Generate cover with AI')}}">
                                      @include('elements.icon',['icon'=>'hardware-chip-outline','variant'=>'medium'])
                                  </span>
                            @endif
                            <span class="h-pill h-pill-media h-pill-media--light pointer-cursor mr-1 upload-button" data-toggle="tooltip" data-placement="top" title="{{__('Upload cover image')}}">
                             @include('elements.icon',['icon'=>'image','variant'=>'medium'])
                        </span>
                            <span class="h-pill h-pill-media h-pill-media--light pointer-cursor" onclick="ProfileSettings.removeUserAsset('cover')" data-toggle="tooltip" data-placement="top" title="{{__('Remove cover image')}}">
                            @include('elements.icon',['icon'=>'close','variant'=>'medium'])
                        </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="card avatar-holder">
                <img class="card-img-top" src="{{Auth::user()->avatar}}">

                <div class="profile-asset-loading d-none">
                    <div class="shimmer"></div>
                </div>

                <div class="card-img-overlay d-flex justify-content-center align-items-center">
                    <div class="actions-holder d-none">
                        <div class="d-flex">
                            @if(getSetting('ai.images_enabled'))
                                <span class="h-pill h-pill-media h-pill-media--light pointer-cursor mr-1"
                                      onclick="ProfileSettings.generateUserAsset('avatar')"
                                      data-toggle="tooltip" data-placement="top" title="{{__('Generate avatar with AI')}}">
                                      @include('elements.icon',['icon'=>'hardware-chip-outline','variant'=>'medium'])
                                  </span>
                            @endif
                            <span class="h-pill h-pill-media h-pill-media--light pointer-cursor mr-1 upload-button" data-toggle="tooltip" data-placement="top" title="{{__('Upload avatar')}}">
                            @include('elements.icon',['icon'=>'image','variant'=>'medium'])
                        </span>
                            <span class="h-pill h-pill-media h-pill-media--light pointer-cursor" onclick="ProfileSettings.removeUserAsset('avatar')" data-toggle="tooltip" data-placement="top" title="{{__('Remove avatar')}}">
                             @include('elements.icon',['icon'=>'close','variant'=>'medium'])
                        </span>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(session('success'))
        <div class="alert alert-success text-white font-weight-bold mt-2" role="alert">
            {{session('success')}}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    <div class="form-group">
        <label for="username">{{__('Username')}}</label>
        <input class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}" id="username" name="username" aria-describedby="emailHelp" value="{{ old('username', Auth::user()->username) }}">
        @if($errors->has('username'))
            <span class="invalid-feedback" role="alert">
                <strong>{{$errors->first('username')}}</strong>
            </span>
        @endif

    </div>
    <div class="form-group">
        <label for="name">{{__('Full name')}}</label>
        <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="name" name="name" aria-describedby="emailHelp" value="{{ old('name', Auth::user()->name) }}">
        @if($errors->has('name'))
            <span class="invalid-feedback" role="alert">
                <strong>{{$errors->first('name')}}</strong>
            </span>
        @endif
    </div>
    <div class="form-group">
        <div class="d-flex justify-content-between">
            <label for="bio">
                {{__('Bio')}}
            </label>
            <div>
                @if(getSetting('ai.text_enabled'))
                    <a href="javascript:void(0)"
                       class="ai-suggest-link"
                       data-ai-type="profile_bio"
                       data-loading-text="{{ __('Generating') }}">
                        {{ __('AI Suggestion') }}
                    </a>
                @endif
            </div>
        </div>
        <textarea class="form-control {{ $errors->has('bio') ? 'is-invalid' : '' }}" id="bio" name="bio" rows="3" spellcheck="false">{{ old('bio', Auth::user()->bio) }}</textarea>
        @if($errors->has('bio'))
            <span class="invalid-feedback" role="alert">
                <strong>{{$errors->first('bio')}}</strong>
            </span>
        @endif
    </div>
    <div class="form-group">
        <label for="birthdate">{{__('Birthdate')}}</label>
        <input type="date" class="form-control {{ $errors->has('location') ? 'is-invalid' : '' }}" id="birthdate" name="birthdate" aria-describedby="emailHelp"  value="{{ old('birthdate', Auth::user()->birthdate) }}" max="{{$minBirthDate}}">
        @if($errors->has('birthdate'))
            <span class="invalid-feedback" role="alert">
                <strong>{{$errors->first('birthdate')}}</strong>
            </span>
        @endif
    </div>

    <div class="d-flex flex-row">
        <div class="{{getSetting('profiles.allow_gender_pronouns') ? 'w-50' : 'w-100'}} pr-2">
            <div class="form-group">
                <label for="gender">{{__('Gender')}}</label>
                <select class="form-control" id="gender" name="gender" >
                    <option value=""></option>
                    @foreach($genders as $gender)
                        <option value="{{$gender->id}}" {{ (string) old('gender', Auth::user()->gender_id) === (string) $gender->id ? 'selected' : '' }}>{{__($gender->gender_name)}}</option>
                    @endforeach
                </select>
                @if($errors->has('gender'))
                    <span class="invalid-feedback" role="alert">
                <strong>{{$errors->first('gender')}}</strong>
            </span>
                @endif
            </div>
        </div>

        @if(getSetting('profiles.allow_gender_pronouns'))
            <div class="w-50 pl-2">
                <div class="form-group">
                    <label for="pronoun">{{__('Gender pronoun')}}</label>
                    <input class="form-control {{ $errors->has('location') ? 'is-invalid' : '' }}" id="pronoun" name="pronoun" aria-describedby="emailHelp"  value="{{ old('pronoun', Auth::user()->gender_pronoun) }}">
                    @if($errors->has('pronoun'))
                        <span class="invalid-feedback" role="alert">
                        <strong>{{$errors->first('pronoun')}}</strong>
                    </span>
                    @endif
                </div>
            </div>
        @endif

    </div>

    <div class="d-flex flex-row">
        <div class="form-group w-50 pr-2">
            <label for="country">{{__('Country')}}</label>
            <select class="form-control" id="country" name="country" >
                <option value=""></option>
                @foreach($countries as $country)
                    <option value="{{$country->id}}" {{ (string) old('country', Auth::user()->country_id) === (string) $country->id ? 'selected' : '' }}>{{__($country->name)}}</option>
                @endforeach
            </select>
            @if($errors->has('country'))
                <span class="invalid-feedback" role="alert">
                    <strong>{{$errors->first('country')}}</strong>
                </span>
            @endif
        </div>

        <div class="form-group w-50 pl-2">
            <label for="location">{{__('Location')}}</label>
            <input class="form-control {{ $errors->has('location') ? 'is-invalid' : '' }}" id="location" name="location" aria-describedby="emailHelp"  value="{{ old('location', Auth::user()->location) }}">
            @if($errors->has('location'))
                <span class="invalid-feedback" role="alert">
                    <strong>{{$errors->first('location')}}</strong>
                </span>
            @endif
        </div>
    </div>

    <div class="form-group">
        <label for="website" value="{{Auth::user()->website}}">{{__('Website URL')}}</label>
        <input type="url" class="form-control {{ $errors->has('website') ? 'is-invalid' : '' }}" id="website" name="website" aria-describedby="emailHelp" value="{{ old('website', Auth::user()->website) }}">
        @if($errors->has('website'))
            <span class="invalid-feedback" role="alert">
                <strong>{{$errors->first('website')}}</strong>
            </span>
        @endif
    </div>

    @if(getSetting('ai.text_enabled') || getSetting('ai.text_enabled'))
        @include('elements.settings.ai-settings')
    @endif

    @if(getSetting('profiles.social_links_enabled'))
        @include('elements.settings.settings-social-network-selector')
    @endif

    @if(getSetting('profiles.spotify_enabled'))
        @include('elements.settings.settings-spotify-inline', ['spotifyAccount' => $spotifyAccount ?? null])
    @endif

    <button class="btn btn-primary btn-block rounded mr-0" type="submit">{{__('Save')}}</button>
</form>
