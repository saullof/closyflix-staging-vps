/**
 * Story creator component
 */
"use strict";
/* global app, launchToast, trans, FileUpload, SoundSelect, updateButtonState, redirect, AiSuggestions, mediaSettings, isAllowedToPost */

$(function () {
    StoryCreator.init();
    if (app.ai_text_enabled && window.AiSuggestions) {
        AiSuggestions.init({
            type: 'story',
            target: '#story-text-only', // fallback only
            editorGetter: null
        });
    }
});

// Alert if user attempts to leave the page while uploading an asset
window.addEventListener('beforeunload', function (event) {
    // Forcing a dialog when a file is being uploaded/video transcoded
    if(FileUpload.isTranscodingVideo === true || FileUpload.isLoading === true){
        event.returnValue = trans('Are you sure you want to leave?');
    }
});

var StoryCreator = {
    mode: "media", // "media" | "text"
    attachmentID: null, // active attachment for this story (singular)

    soundSelectMedia: null,
    soundSelectText: null,

    bgPreset: "grad_default", // or "solid_black"
    bgColor: "#000000",
    textPos: { x: 0.5, y: 0.5 }, // normalized center
    isSubmitting: false,

    ensureMediaPreview: function () {
        if (this.mode !== "media") return;
        if (!this.attachmentID) return;

        // if media element already exists, don't touch it (prevents restart)
        if (this.el.preview.find("video, img").length) {
            return;
        }

        var attachment = this.getAttachmentById(this.attachmentID);
        if (attachment) {
            this.renderMediaPreview(attachment);
        }
    },

    // Cached DOM elements
    el: {
        preview: null,
        previewText: null,
        tabs: null,
        bgButtons: null,
        textOnly: null,
        textOverlay: null,
        attachmentsCounter: null,
        isPublic: null,
        shareButton: null
    },

    /**
     * Init entry
     */
    init: function () {
        this.cacheDom();

        // Sound selects init (two instances)
        if (window.SoundSelect && typeof SoundSelect.create === "function") {
            this.soundSelectMedia = SoundSelect.create({
                baseUrl: app.baseUrl,
                inputId: "media-storySoundSelect",
                soundIdId: "media-storySoundId",
                soundTitleId: "media-storySoundTitle",
                soundArtistId: "media-storySoundArtist",
                soundUrlId: "media-storySoundUrl",
                soundStartMsId: "media-storySoundStartMs",
                enablePreview: false,
                previewSeconds: 8
            });
            this.soundSelectMedia.init();

            this.soundSelectText = SoundSelect.create({
                baseUrl: app.baseUrl,
                inputId: "text-storySoundSelect",
                soundIdId: "text-storySoundId",
                soundTitleId: "text-storySoundTitle",
                soundArtistId: "text-storySoundArtist",
                soundUrlId: "text-storySoundUrl",
                soundStartMsId: "text-storySoundStartMs",
                enablePreview: false,
                previewSeconds: 8
            });
            this.soundSelectText.init();
        } else {
            // eslint-disable-next-line no-console
            console.warn("[StoryCreator] SoundSelect module not found or missing create()");
        }

        this.bindEvents();
        this.bindDragText();

        if (this.canUpload()) {
            // Reuse your existing uploader engine
            // TODO: Check max file uploads and other limits
            FileUpload.initDropZone(".story-upload-zone", "/attachment/upload/story", false, {
                maxFiles: 1
            });

            this.hookIntoUploader();
        } else {
            this.disableUploader();
        }

        this.setInitialBgChoice();
        this.updatePreview();
        this.updateSoundState();
    },

    canUpload: function () {
        if (typeof isAllowedToPost !== "undefined" && !isAllowedToPost) {
            return false;
        }

        return !(typeof mediaSettings !== "undefined" && mediaSettings.initUploader === false);
    },

    disableUploader: function () {
        var $zone = $(".story-upload-zone");

        $zone
            .addClass("is-disabled disabled")
            .attr("aria-disabled", "true");

        if (!$zone.find(".upload-zone-disabled-message").length) {
            $zone.append('<div class="upload-zone-disabled-message">' + trans("Drop files here to upload") + "</div>");
        }
    },

    /**
     * Cache frequently used DOM elements
     */
    cacheDom: function () {
        this.el.preview = $("#story-preview");
        this.el.previewText = $("#story-preview-text");
        this.el.tabs = $("#story-type-tabs a[data-toggle='pill']");
        this.el.bgButtons = $("#story-bg-picker .story-bg-choice");
        this.el.textOnly = $("#story-text-only");
        this.el.textOverlay = $("#story-text-overlay");

        this.el.linkUrlMedia = $("#media-story-link-url");
        this.el.linkTextMedia = $("#media-story-link-text");

        this.el.linkUrlText = $("#text-story-link-url");
        this.el.linkTextText = $("#text-story-link-text");

        this.el.attachmentsCounter = $("#story-attachments-counter");

        this.el.isPublic = $("#is_public");
        this.el.shareButton = $("#btn-story-share");
    },

    /**
     * Bind UI events
     */
    bindEvents: function () {
        var self = this;

        // Tabs: media vs text
        this.el.tabs.on("shown.bs.tab", function (e) {
            var target = $(e.target).attr("href");
            self.mode = (target === "#story-tab-media") ? "media" : "text";

            if (self.mode === "media") {
                // let media/no-media rules control background again
                self.clearPresetBgClass();
                self.ensureMediaPreview(); // restore media when coming back
            }

            self.updatePreview();
            self.updateSoundState();
        });

        // Text-only story content
        this.el.textOnly.on("input", function () {
            if (self.mode === "text") {
                self.updatePreview();
            }
        });

        // Text overlay for media
        this.el.textOverlay.on("input", function () {
            if (self.mode === "media") {
                self.updatePreview();
            }
        });

        // Background color buttons
        this.el.bgButtons.on("click", function () {
            self.el.bgButtons.removeClass("is-active");
            $(this).addClass("is-active");

            // NEW: store preset key
            self.bgPreset = $(this).data("preset") || "solid_black";

            // optional fallback if you still want it
            self.bgColor = $(this).data("color") || "#000000";

            if (self.mode === "text") {
                self.updatePreview();
            }
        });

        // Share story
        this.el.shareButton.on("click", function () {
            self.submit();
        });
    },

    /**
     * Mark the first bg choice as active by default (if any)
     */
    setInitialBgChoice: function () {
        if (!this.el.bgButtons.length) return;

        // Prefer grad_default if you have it, else first button
        var $initial = this.el.bgButtons.filter('[data-preset="grad_default"]').first();
        if (!$initial.length) $initial = this.el.bgButtons.first();

        this.el.bgButtons.removeClass("is-active");
        $initial.addClass("is-active");

        this.bgPreset = $initial.data("preset") || "solid_black";
        this.bgColor = $initial.data("color") || this.bgColor;
    },


    /**
     * Hook into FileUpload's dropzone events
     */
    hookIntoUploader: function () {
        var self = this;

        if (!FileUpload || !FileUpload.myDropzone) {
            return;
        }

        FileUpload.myDropzone.on("addedfile", function () {
            // Keep only the newest file (max 1)
            if (FileUpload.myDropzone.files.length > 1) {
                FileUpload.myDropzone.removeFile(FileUpload.myDropzone.files[0]);
            }
        });

        FileUpload.myDropzone.on("success", function (file, response) {
            if (!response || !response.success) return;

            if (response.attachmentID) {
                // IMPORTANT: store it on the file for removedfile
                file.upload = file.upload || {};
                file.upload.attachmentID = response.attachmentID;

                self.setActiveAttachment(response.attachmentID);
            }
        });

        FileUpload.myDropzone.on("removedfile", function (file) {
            if (file && file.upload && file.upload.attachmentID) {
                self.onAttachmentRemoved(file.upload.attachmentID);
                self.updatePreview();
                self.updateSoundState();
            }
        });
    },

    /**
     * Select an attachment as the active story media
     */
    setActiveAttachment: function (attachmentID) {
        this.attachmentID = attachmentID;

        var attachment = this.getAttachmentById(attachmentID);
        if (!attachment) {
            return;
        }

        this.renderMediaPreview(attachment);
        this.updatePreview(); // ensures overlay text remains correct
        this.updateSoundState();
    },

    /**
     * Handle removed attachment
     */
    onAttachmentRemoved: function (attachmentID) {
        // if removed one is currently active, clear it and pick the last remaining one (if any)
        if (this.attachmentID === attachmentID) {
            this.attachmentID = null;
            this.clearMediaPreview();

            var last = this.getLastAttachment();
            if (last) {
                this.setActiveAttachment(last.attachmentID);
            }
        }
    },

    /**
     * Find attachment in FileUpload.attachaments by ID
     */
    getAttachmentById: function (attachmentID) {
        if (!FileUpload || !Array.isArray(FileUpload.attachaments)) {
            return null;
        }

        for (var i = 0; i < FileUpload.attachaments.length; i++) {
            if (FileUpload.attachaments[i].attachmentID === attachmentID) {
                return FileUpload.attachaments[i];
            }
        }

        return null;
    },

    /**
     * Get last attachment in list
     */
    getLastAttachment: function () {
        if (!FileUpload || !Array.isArray(FileUpload.attachaments) || !FileUpload.attachaments.length) {
            return null;
        }
        return FileUpload.attachaments[FileUpload.attachaments.length - 1];
    },

    /**
     * Render media in the big preview (left)
     * Expects attachment object: { attachmentID, path, type, thumbnail }
     */
    renderMediaPreview: function (attachment) {
        if (this.mode !== "media") {
            return;
        }

        this.el.preview.find("img, video").remove();

        if (attachment.type === "video") {
            var $video = $("<video />", {
                src: attachment.path,
                autoplay: true,
                loop: true,
                playsinline: true,
                muted: "muted"
            });

            // Force mute at DOM/property level (most reliable)
            $video.prop("muted", true);
            $video.prop("volume", 0);
            $video.prop("controls", false);

            this.el.preview.prepend($video);
            return;
        }

        // image fallback
        var src = attachment.thumbnail ? attachment.thumbnail : attachment.path;

        var $img = $("<img />", {
            src: src,
            class: "img-fluid"
        });

        this.el.preview.prepend($img);
    },

    /**
     * Remove any media from the big preview
     */
    clearMediaPreview: function () {
        this.el.preview.find("img, video").remove();
    },

    /**
     * Main preview update based on mode / text / bg color
     * - text mode: background + text
     * - media mode: keeps media, just updates overlay text
     */
    updatePreview: function () {
        // TEXT MODE
        if (this.mode === "text") {
            this.clearMediaPreview();

            var text = (this.el.textOnly.val() || "");
            this.el.previewText.text(text).show();
            this.applyTextPos();

            // remove media-state background control
            this.el.preview.removeClass("is-no-media");

            // apply preset class
            this.clearPresetBgClass();
            this.el.preview.addClass("story-bg--" + (this.bgPreset || "solid_black"));

            this.el.preview.toggleClass("is-empty", !text.trim());
            return;
        }

        // MEDIA MODE
        this.clearPresetBgClass();

        var overlayText = (this.el.textOverlay.val() || "");
        this.el.previewText.text(overlayText).show();
        this.applyTextPos();
        var hasMedia = !!this.attachmentID;
        var hasText = !!overlayText.trim();

        this.el.preview.toggleClass("is-no-media", !hasMedia);
        this.el.preview.toggleClass("is-empty", (!hasMedia && !hasText));
    },

    /**
     * Submit story draft/publish request
     * Uses attachmentID (singular), not "frames"
     */
    submit: function () {
        // prevent double submit
        if (this.isSubmitting) return;

        var self = this;

        var linkUrl, linkText;

        if (this.mode === "text") {
            linkUrl = (this.el.linkUrlText.val() || "").trim();
            linkText = (this.el.linkTextText.val() || "").trim();
        } else {
            linkUrl = (this.el.linkUrlMedia.val() || "").trim();
            linkText = (this.el.linkTextMedia.val() || "").trim();
        }

        // simple URL guard
        if (linkUrl && !/^https?:\/\//i.test(linkUrl)) {
            launchToast("danger", trans("Error"), trans("Link must start with http:// or https://"));
            return;
        }

        var payload = {
            mode: this.mode,
            text: (this.mode === "text") ? this.el.textOnly.val() : this.el.textOverlay.val(),
            overlay_x: this.textPos.x,
            overlay_y: this.textPos.y,
            bg_preset: (this.mode === "text") ? this.bgPreset : null,
            is_public: this.el.isPublic && this.el.isPublic.length && this.el.isPublic.is(":checked") ? 1 : 0,
            attachmentID: (this.mode === "media") ? this.attachmentID : null,
            link_url: linkUrl || null,
            link_text: linkText || null,
            sound_id: this.getActiveSoundId()
        };

        // basic guard
        if (payload.mode === "media" && !payload.attachmentID) {
            launchToast("danger", trans("Error"), trans("Please upload a photo or video first."));
            return;
        }

        // ---- UI lock ----
        this.isSubmitting = true;

        // keep original label, and show spinner
        updateButtonState("loading", this.el.shareButton, trans("Sharing…"), "light");
        // optional: also disable tabs/inputs if you want:
        // this.el.tabs.addClass("disabled");

        $.ajax({
            method: "POST",
            url: app.baseUrl + "/stories/create",
            data: payload,
            dataType: "json",

            success: function (response) {
                launchToast("success", trans("Story shared"), trans("Your story published."));

                if (response && response.redirect_url) {
                    redirect(response.redirect_url);
                }
            },

            error: function (xhr) {
                var message = trans("Something went wrong while posting your story.");

                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                launchToast("danger", trans("Couldn’t share story"), message);
            },

            complete: function () {
                self.isSubmitting = false;

                // restore button
                updateButtonState("loaded", self.el.shareButton, trans("Share story"));
            }
        });
    },

    clearPresetBgClass: function () {
        this.el.preview.removeClass(function (i, cls) {
            return (cls.match(/\bstory-bg--\S+/g) || []).join(" ");
        });
    },

    bindDragText: function () {
        var self = this;
        var $text = this.el.previewText;
        var $canvas = this.el.preview;

        if (!$text.length || !$canvas.length) return;

        var dragging = false;
        var start = { x: 0, y: 0 };
        var startPos = { x: 0.5, y: 0.5 };

        function clamp(v, min, max){ return Math.max(min, Math.min(max, v)); }

        function applyPos() {
            // position text by translating from center
            $text.css({
                left: (self.textPos.x * 100) + "%",
                top:  (self.textPos.y * 100) + "%",
                transform: "translate(-50%, -50%)"
            });
        }

        // Make the text layer positioned
        $text.css({ position: "absolute" });
        applyPos();

        $text.on("pointerdown", function (e) {
            if (!String($text.text() || "").trim()) return;

            if (e.cancelable) e.preventDefault(); // ✅ add this

            dragging = true;
            $text.addClass("is-dragging");
            $text[0].setPointerCapture(e.pointerId);

            start.x = e.clientX;
            start.y = e.clientY;
            startPos.x = self.textPos.x;
            startPos.y = self.textPos.y;
        });

        $text.on("pointermove", function (e) {
            if (!dragging) return;

            var rect = $canvas[0].getBoundingClientRect();
            var dx = (e.clientX - start.x) / rect.width;
            var dy = (e.clientY - start.y) / rect.height;

            self.textPos.x = clamp(startPos.x + dx, 0.05, 0.95);
            self.textPos.y = clamp(startPos.y + dy, 0.08, 0.92);

            // Optional snap (center + thirds)
            var snapsX = [0.5, 1/3, 2/3];
            var snapsY = [0.2, 0.5, 0.8];
            var snapDist = 0.035; // ~3.5% of width/height

            snapsX.forEach(function(s){ if (Math.abs(self.textPos.x - s) < snapDist) self.textPos.x = s; });
            snapsY.forEach(function(s){ if (Math.abs(self.textPos.y - s) < snapDist) self.textPos.y = s; });

            applyPos();
        });

        $text.on("pointerup pointercancel", function () {
            if (!dragging) return;
            dragging = false;
            $text.removeClass("is-dragging");
        });
    },

    applyTextPos: function () {
        this.el.previewText.css({
            left: (this.textPos.x * 100) + "%",
            top:  (this.textPos.y * 100) + "%",
            transform: "translate(-50%, -50%)"
        });
    },

    getActiveSoundId: function () {
        var elId = (this.mode === "text") ? "text-storySoundId" : "media-storySoundId";
        var el = document.getElementById(elId);
        var v = el ? String(el.value || "").trim() : "";
        return v || null;
    },

    clearMediaSoundSelection: function () {
        var hid = document.getElementById("media-storySoundId");
        if (hid) hid.value = "";

        if (this.soundSelectMedia && this.soundSelectMedia.selectize) {
            // eslint-disable-next-line no-empty
            try { this.soundSelectMedia.selectize.clear(true); } catch (e) {}
        } else {
            // fallback
            var input = document.getElementById("media-storySoundSelect");
            if (input && input.selectize) input.selectize.clear(true);
        }
    },

    setMediaSoundDisabled: function (disabled) {
        var helpDefault = document.getElementById("media-storySoundHelpDefault");
        var helpVideo = document.getElementById("media-storySoundHelpVideo");

        if (helpDefault) helpDefault.classList.toggle("d-none", !!disabled);
        if (helpVideo) helpVideo.classList.toggle("d-none", !disabled);

        if (this.soundSelectMedia && this.soundSelectMedia.selectize) {
            if (disabled) this.soundSelectMedia.selectize.disable();
            else this.soundSelectMedia.selectize.enable();
        } else {
            var input = document.getElementById("media-storySoundSelect");
            if (input && input.selectize) {
                if (disabled) input.selectize.disable();
                else input.selectize.enable();
            } else if (input) {
                input.disabled = !!disabled;
            }
        }
    },

    updateSoundState: function () {
        // Only media selector gets disabled on video
        if (this.mode === "media" && this.isActiveMediaVideo()) {
            this.clearMediaSoundSelection();
            this.setMediaSoundDisabled(true);
            return;
        }
        this.setMediaSoundDisabled(false);
    },

    isActiveMediaVideo: function () {
        if (this.mode !== "media") return false;
        if (!this.attachmentID) return false;

        var att = this.getAttachmentById(this.attachmentID);
        return !!(att && att.type === "video");
    },

};
