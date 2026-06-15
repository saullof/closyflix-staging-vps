<div class="modal fade" tabindex="-1" role="dialog" id="language-selector-dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{__('Change language')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>{{__('Select the language you want to use.')}}</p>
                <div class="input-group">
                    <select class="form-control" id="language_code" name="language_code" >
                        @foreach(LocalesHelper::getAvailableLanguages() as $languageCode)
                            @if(LocalesHelper::getLanguageName($languageCode))
                                <option value="{{$languageCode}}" {{LocalesHelper::getUserPreferredLocale(request()) == $languageCode ? 'selected' : ''}}>{{ucfirst(__(LocalesHelper::getLanguageName($languageCode)))}}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div>
                </div>
                <div>
                    <button type="button" class="btn btn-primary mb-0" onclick="setUserLanguage()">{{__('Save')}}</button>
                </div>
            </div>
        </div>
    </div>
</div>
