/**
 * Installer component JS side
 */
"use strict";
/* global trans, updateButtonState */

$(function () {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Preventing double click on final install step form
    $('.finalInstallStepForm').on('submit', function (e) {
        updateButtonState('loading', $('.finalInstallStepForm .btn-primary'), 'Installing', 'white');
        if(Installer.state.isInstalling === true){
            e.preventDefault();
        }
        setTimeout(function () {
            Installer.state.isInstalling = true;
        }, 100);
    });

});

// eslint-disable-next-line no-unused-vars
var Installer = {

    state: {
        isInstalling : false,
    },

    /**
     * Toggles password field between password and text types
     */
    togglePasswordField: function (field = 'password') {
        if($('#'+field).attr('type') === 'password'){
            $('#'+field).attr('type','text');
            $('.hide-pass').removeClass('d-none');
            $('.show-pass').addClass('d-none');
            $('.h-pill').attr('data-original-title',trans('Hide password')).tooltip('update').tooltip('show');
        }
        else{
            $('#'+field).attr('type','password');
            $('.show-pass').removeClass('d-none');
            $('.hide-pass').addClass('d-none');
            $('.h-pill').attr('data-original-title',trans('Show password')).tooltip('update').tooltip('show');
        }
    },



};
