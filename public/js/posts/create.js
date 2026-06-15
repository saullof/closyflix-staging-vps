/*
* Post create page
 */
"use strict";
/* global PostCreate, FileUpload, mediaSettings, isAllowedToPost, AiSuggestions, app, trans, TextareaHighlighter, MentionSuggestions, app */

$(function () {
    // Initing button save
    $('.post-create-button').on('click',function () {
        PostCreate.save('create');
    });

    $('.draft-clear-button').on('click',function () {
        PostCreate.clearDraft();
    });
    // Populating draft data, if available
    const draftData = PostCreate.populateDraftData();
    PostCreate.initPostDraft(draftData);
    if(isAllowedToPost){
        // Initiating file manager
        FileUpload.initDropZone('.dropzone','/attachment/upload/post', mediaSettings.use_chunked_uploads);
    }
    if (app.ai_text_enabled) {
        AiSuggestions.init({
            target: '#dropzone-uploader',
            editorGetter: null, // no EasyMDE on post create (unless you do have one)
        });
    }

    TextareaHighlighter.init('#dropzone-uploader');
    $('#dropzone-uploader').on('input', function () {
        $(this).removeClass('is-invalid');
    });
    if(app.enable_mention_suggestions && app.enable_mentions){
        MentionSuggestions.init({
            target: '#dropzone-uploader',
            source: window.app.mentionContacts
        });
    }

});


// Saving draft data before unload
window.addEventListener('beforeunload', function (event) {
    // Forcing a dialog when a file is being uploaded/video transcoded
    if(FileUpload.isTranscodingVideo === true || FileUpload.isLoading === true){
        event.returnValue = trans('Are you sure you want to leave?');
    }
    if(!PostCreate.isSavingRedirect){
        PostCreate.saveDraftData();
    }
});
