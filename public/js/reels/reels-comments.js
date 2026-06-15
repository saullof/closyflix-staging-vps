/**
 * Comments panel workflow for the reels viewer.
 */
"use strict";
/* global ReelsApi, ReelsRenderer, Lists, hideDialog, showDialog, trans, updateButtonState */

var ReelsComments = {
    toggleCommentsPanel: function () {
        if (this.isCommentsPanelVisible()) {
            this.closeCommentsPanel();
            return;
        }

        this.openCommentsPanel();
    },

    openCommentsPanel: function () {
        this.commentsPanelOpen = true;
        this.showCommentsPanel();
    },

    showCommentsPanel: function () {
        var video = this.$feed.find(".reel-viewer-video").get(0);
        var shouldPauseForPanel = this.isMobileViewer() || this.hasActiveSoundtrack();

        this.$feed.find(".reel-viewer-comments").removeClass("is-closed").addClass("is-open");
        this.$feed.find(".reel-viewer").addClass("comments-open");

        if (shouldPauseForPanel) {
            this.wasPlayingBeforeComments = !!(video && !video.paused && this.desiredPlayback);
            if (this.wasPlayingBeforeComments) {
                this.desiredPlayback = false;
                video.pause();
            }
        } else {
            this.pauseActiveSoundtrack();
        }
    },

    closeCommentsPanel: function (options) {
        options = options || {};
        var shouldResume = options.resumePlayback !== false;

        if (!options.preservePreference) {
            this.commentsPanelOpen = false;
        }

        this.$feed.find(".reel-viewer-comments").removeClass("is-open").addClass("is-closed");
        this.$feed.find(".reel-viewer").removeClass("comments-open");
        this.$feed.find(".reel-comment-form input").trigger("blur");
        this.setCommentKeyboardOpen(false);

        if (shouldResume && this.wasPlayingBeforeComments) {
            this.desiredPlayback = true;
            this.playActiveVideo();
        } else if (!shouldResume && this.wasPlayingBeforeComments) {
            this.desiredPlayback = true;
        }

        this.wasPlayingBeforeComments = false;
    },

    applyCommentsPanelPreference: function () {
        if (this.commentsPanelOpen === null) {
            this.commentsPanelOpen = !this.isMobileViewer();
        }

        if (this.commentsPanelOpen) {
            this.showCommentsPanel();
            return;
        }

        this.closeCommentsPanel({resumePlayback: false, preservePreference: true});
    },

    isMobileViewer: function () {
        return window.matchMedia && window.matchMedia("(max-width: 767.98px)").matches;
    },

    isCommentsPanelVisible: function () {
        var panel = this.$feed.find(".reel-viewer-comments");
        if (!panel.length) {
            return false;
        }

        if (this.isMobileViewer()) {
            return panel.hasClass("is-open");
        }

        return !panel.hasClass("is-closed");
    },

    fetchComments: function (callback) {
        var self = this;
        var reel = this.getActiveReel();
        var reelId;
        if (!reel) return;

        reelId = reel.id;

        ReelsApi.fetchComments(reelId).done(function (response) {
            if (String(self.getActiveReelId()) !== String(reelId)) {
                return;
            }

            self.renderComments(response.comments || []);
            if (typeof callback === "function") {
                callback();
            }
        }).fail(function () {
            if (String(self.getActiveReelId()) !== String(reelId)) {
                return;
            }

            self.$feed.find(".reel-comments-list").html('<div class="text-danger">' + self.escape(trans("Could not load comments.")) + "</div>");
        });
    },

    renderComments: function (comments) {
        this.$feed.find(".reel-comments-list").html(ReelsRenderer.renderComments(comments));
    },

    renderComment: function (comment) {
        return ReelsRenderer.renderComment(comment);
    },

    renderEmptyCommentsState: function () {
        this.$feed.find(".reel-comments-list").html(ReelsRenderer.renderEmptyCommentsState());
    },

    toggleCommentReaction: function ($button) {
        var self = this;
        var $actions = $button.closest(".reel-comment-actions");
        var commentId = $actions.data("comment-id");
        var lockKey = "comment-reaction-" + commentId;
        var action = $button.hasClass("is-active") ? "remove" : "add";

        if (!this.beginAction(lockKey, $button)) {
            return;
        }

        this.postAction("/reels/reaction", {type: "comment", id: commentId, action: action}, function (response) {
            $button.toggleClass("is-active text-primary", action === "add");
            $button.find("span").text(self.formatCount(response.reactions || 0));
        }, function () {
            self.endAction(lockKey, $button);
        });
    },

    reportComment: function ($button) {
        if (!window.Lists || typeof Lists.showReportBox !== "function") {
            return;
        }

        var $actions = $button.closest(".reel-comment-actions");
        this.showReportBoxAfterClosingViewer($actions.data("user-id"), null, null, null, null, null, $actions.data("comment-id"));
    },

    deleteComment: function ($button) {
        var $actions = $button.closest(".reel-comment-actions");
        this.pendingDeleteCommentId = $actions.data("comment-id");
        showDialog("reel-comment-delete-dialog");
    },

    confirmDeleteComment: function () {
        var self = this;
        var commentId = this.pendingDeleteCommentId;
        var $button = this.$feed.find(".reel-comment-actions").filter(function () {
            return String($(this).data("comment-id")) === String(commentId);
        }).find(".reel-comment-delete").first();
        var $dialogButton = $("#reel-comment-delete-dialog .btn-warning").first();
        var lockKey = "comment-delete-" + commentId;

        if (!commentId) {
            hideDialog("reel-comment-delete-dialog");
            return;
        }

        if (!this.beginAction(lockKey, $button.add($dialogButton))) {
            return;
        }

        this.ajaxAction("DELETE", "/reels/comments/delete", {comment_id: commentId}, function (response) {
            hideDialog("reel-comment-delete-dialog");
            self.pendingDeleteCommentId = null;
            self.updateCommentCount(response.comments || 0);
            self.removeComment(self.$feed.find(".reel-comment").filter(function () {
                return String($(this).data("comment-id")) === String(commentId);
            }), function () {
                if (!self.$feed.find(".reel-comment").length) {
                    self.renderEmptyCommentsState();
                }
            });
        }, function () {
            self.endAction(lockKey, $button.add($dialogButton));
        });
    },

    addComment: function ($form) {
        var self = this;
        var reel = this.getActiveReel();
        var $input = $form.find("input");
        var message = ($input.val() || "").trim();

        if ($form.data("is-submitting")) {
            return;
        }

        if (!reel || !message.length) {
            return;
        }

        $form.data("is-submitting", true);
        this.setCommentFormLoading($form, true);

        this.postAction("/reels/comments/add", {reel_id: reel.id, message: message}, function (response) {
            $input.val("");
            $form.data("is-submitting", false);
            self.setCommentFormLoading($form, false);
            self.updateCommentCount(response.comments || 0);

            if (response.comment) {
                self.prependComment(response.comment);
                self.settleCommentInputAfterSubmit($input);
                return;
            }

            self.fetchComments(function () {
                self.settleCommentInputAfterSubmit($input);
                self.$feed.find(".reel-comments-list").scrollTop(0);
            });
        }, null, function () {
            $form.data("is-submitting", false);
            self.setCommentFormLoading($form, false);
        });
    },

    prependComment: function (comment) {
        var $list = this.$feed.find(".reel-comments-list");
        $list.find(".reel-comments-empty").remove();
        $list.prepend(this.renderComment(comment));
        $list.scrollTop(0);
    },

    removeComment: function ($comment, callback) {
        if (!$comment.length) {
            if (typeof callback === "function") {
                callback();
            }
            return;
        }

        $comment.addClass("is-removing");
        window.setTimeout(function () {
            $comment.remove();
            if (typeof callback === "function") {
                callback();
            }
        }, 220);
    },

    setCommentFormLoading: function ($form, isLoading) {
        var $input = $form.find("input");
        var $button = $form.find("button[type='submit']");

        $input.prop("disabled", isLoading);
        $button.prop("disabled", isLoading);

        if (typeof updateButtonState === "function") {
            updateButtonState(isLoading ? "loading" : "loaded", $button, trans("Send"), "white");
            $button.prop("disabled", isLoading);
        } else {
            $button.toggleClass("disabled", isLoading);
        }
    }
};

window.ReelsComments = ReelsComments;
