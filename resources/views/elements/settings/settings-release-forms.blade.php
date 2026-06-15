@if(session('success'))
    <div class="alert alert-success text-white font-weight-bold mt-2" role="alert">
        {{session('success')}}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-warning text-white font-weight-bold mt-2" role="alert">
        {{session('error')}}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="mt-3">
    <p class="text-muted">
        {{__('Upload signed release forms for people who may appear in your content. Forms are reviewed by admins before they are marked as approved.')}}
    </p>

    @if(getSetting('compliance.release_forms_custom_message_box'))
        @include('elements.settings.custom-info-box', [
            'message' => getSetting('compliance.release_forms_custom_message_box'),
            'classes' => 'mt-3 mb-3',
        ])
    @endif

    <form class="release-forms-form verify-form" action="{{route('my.settings.release-forms.save')}}" method="POST">
        @csrf
        <div class="form-group">
            <label for="release-form-title">{{__('Title')}}</label>
            <input id="release-form-title" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{old('title')}}" placeholder="{{__('Example: Partner release form')}}">
            @error('title')
                <span class="invalid-feedback" role="alert"><strong>{{$message}}</strong></span>
            @enderror
        </div>

        <div class="form-group">
            <label for="release-form-notes">{{__('Notes')}}</label>
            <textarea id="release-form-notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="{{__('Optional notes for admins')}}">{{old('notes')}}</textarea>
            @error('notes')
                <span class="invalid-feedback" role="alert"><strong>{{$message}}</strong></span>
            @enderror
        </div>

        <div class="form-group">
            <label>{{__('Files')}}</label>
            <div class="dropzone-previews dropzone w-100 ppl-0 pr-0 pt-1 pb-1 border rounded"></div>
            <small class="form-text text-muted mb-2">{{__("Allowed file types")}}: {{str_replace(',', ', ', AttachmentHelper::filterExtensions('manualPayments'))}}. {{__("Max size")}}: 4 {{__("MB")}}.</small>
        </div>

        <div class="d-flex flex-row-reverse">
            <button class="btn btn-primary mt-2">{{__("Submit for review")}}</button>
        </div>
    </form>
</div>

<hr class="my-4">

<div class="release-forms-history mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">{{__('Your release forms')}}</h5>
        @if(($releaseForms ?? collect())->count())
            <span class="text-muted small">{{trans_choice(':count form|:count forms', ($releaseForms ?? collect())->count(), ['count' => ($releaseForms ?? collect())->count()])}}</span>
        @endif
    </div>

    @if(($releaseForms ?? collect())->count())
        <div class="release-forms-list list-group list-group-flush border rounded overflow-hidden">
            @foreach(($releaseForms ?? collect()) as $releaseForm)
                @php
                    $statusClass = match ($releaseForm->status) {
                        \App\Model\ReleaseForm::APPROVED_STATUS => 'success',
                        \App\Model\ReleaseForm::REJECTED_STATUS => 'danger',
                        default => 'warning',
                    };
                    $files = is_array($releaseForm->files) ? $releaseForm->files : [];
                @endphp

                <div class="release-form-item list-group-item">
                    <div class="release-form-summary">
                        <div class="release-form-title">{{$releaseForm->title ?: __('Release form')}}</div>
                        <div class="release-form-meta">
                            <span class="badge badge-{{$statusClass}}">{{__(ucfirst($releaseForm->status))}}</span>
                            <span>{{$releaseForm->created_at->format('M j, Y')}}</span>
                        </div>

                        @if($releaseForm->notes)
                            <p class="release-form-notes">{{$releaseForm->notes}}</p>
                        @endif

                        @if($releaseForm->rejection_reason && $releaseForm->status === \App\Model\ReleaseForm::REJECTED_STATUS)
                            <div class="alert alert-warning text-white font-weight-bold mt-3 mb-0" role="alert">
                                {{__('Rejected reason')}}: {{$releaseForm->rejection_reason}}
                            </div>
                        @endif
                    </div>

                    <div class="release-form-actions">
                        @if(!empty($files))
                            <div class="release-form-files">
                                @foreach($files as $index => $file)
                                    <a href="{{Storage::url($file)}}" target="_blank" class="btn btn-sm btn-outline-success release-form-file-btn">
                                        @include('elements.icon', ['icon' => 'document-text-outline', 'centered' => 'false', 'classes' => 'mr-1'])
                                        {{__('File')}} {{$index + 1}}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <button type="button" class="btn btn-sm btn-outline-danger release-form-delete-btn" data-toggle="modal" data-target="#release-form-delete-dialog-{{$releaseForm->id}}">
                            {{__('Delete')}}
                        </button>

                        <form id="release-form-delete-form-{{$releaseForm->id}}" action="{{route('my.settings.release-forms.delete', ['releaseForm' => $releaseForm->id])}}" method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </div>

                @include('elements.standard-dialog', [
                    'dialogName' => 'release-form-delete-dialog-'.$releaseForm->id,
                    'title' => __('Delete release form'),
                    'content' => __('Are you sure you want to delete this release form?'),
                    'actionLabel' => __('Delete'),
                    'actionFunction' => "document.getElementById('release-form-delete-form-".$releaseForm->id."').submit();",
                ])
            @endforeach
        </div>
    @else
        <p class="text-muted">{{__('No release forms uploaded yet.')}}</p>
    @endif
</div>

@include('elements.uploaded-file-preview-template')
