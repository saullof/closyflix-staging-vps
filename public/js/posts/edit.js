/*
* Post create page
 */
"use strict";
/* global FileUpload, PostCreate, PostCreate, postData, trans, TextareaHighlighter,app, MentionSuggestions */

$(function () {
    // Initing button save
    $('.post-create-button').on('click',function () {
        PostCreate.save('update',postData.id);
    });
    PostCreate.initPostDraft(postData,'edit');
    PostCreate.postPrice = postData.price;
    PostCreate.setFreePost(postData.is_free === true || postData.is_free === 1);
    FileUpload.initDropZone('.dropzone','/attachment/upload/post');
    if(postData.hasPoll){
        PostCreate.savePoll();
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
});
