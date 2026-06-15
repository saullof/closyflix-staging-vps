/**
 * Markup helpers for reels grid, viewer and comments.
 */
"use strict";
/* global trans, getStoredSvg */

var ReelsRenderer = {
    renderEmptyState: function (emptyActionUrl, emptyActionLabel) {
        return '<div class="reels-empty-state text-muted">' +
            '<ion-icon name="film-outline"></ion-icon>' +
            '<h6>' + this.escape(trans("No reels yet.")) + '</h6>' +
            (emptyActionUrl
                ? '<a class="btn btn-sm btn-outline-primary mb-0" href="' + this.escapeAttr(emptyActionUrl) + '">' + this.escape(emptyActionLabel) + '</a>'
                : '') +
            '</div>';
    },

    renderUnavailableState: function (baseUrl) {
        return '<div class="reels-unavailable-state">' +
            '<ion-icon name="alert-circle-outline"></ion-icon>' +
            '<h6>' + this.escape(trans("Reel unavailable")) + '</h6>' +
            '<p>' + this.escape(trans("This reel is no longer available.")) + '</p>' +
            '<a class="btn btn-sm btn-outline-primary mb-0" href="' + this.escapeAttr(baseUrl) + '">' + this.escape(trans("Explore reels")) + '</a>' +
        '</div>';
    },

    renderGridCard: function (reel, index) {
        var user = reel.user || {};
        var image = reel.cover || "";
        var username = user.username ? ("@" + user.username) : "";
        var verified = this.renderVerifiedBadge(user.verified, "reel-grid-verified");

        return '' +
            '<button type="button" class="reel-grid-card" data-reel-index="' + this.escapeAttr(index) + '">' +
                '<span class="reel-grid-media">' +
                    (image ? '<img class="reel-grid-poster" src="' + this.escapeAttr(image) + '" alt="">' : '') +
                    '<span class="reel-grid-placeholder' + (image ? " d-none" : "") + '"><ion-icon name="play-outline"></ion-icon><span>' + this.escape(trans("No preview")) + '</span></span>' +
                    '<video class="reel-grid-preview" src="' + this.escapeAttr(reel.src || "") + '" muted playsinline loop preload="none"></video>' +
                    '<span class="reel-grid-gradient"></span>' +
                    this.renderGridStats(reel) +
                '</span>' +
                '<span class="reel-grid-meta">' +
                    '<span class="reel-grid-avatar"><img src="' + this.escapeAttr(user.photo || "") + '" alt=""></span>' +
                    '<span class="reel-grid-title"><span class="reel-grid-username">' + this.escape(username) + '</span>' + verified + '</span>' +
                '</span>' +
            '</button>';
    },

    renderGridStats: function (reel) {
        return '<span class="reel-grid-stats">' +
            '<span><ion-icon name="play-outline"></ion-icon>' + this.escape(this.formatCount(reel.views || 0)) + '</span>' +
            '<span><ion-icon name="heart-outline"></ion-icon>' + this.escape(this.formatCount(reel.reactions || 0)) + '</span>' +
        '</span>';
    },

    renderGridLoader: function (hasMore) {
        return '<div class="reels-grid-loader ' + (hasMore ? "" : "d-none") + '">' +
            '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' +
        '</div>';
    },

    renderVerifiedBadge: function (isVerified, className) {
        var storedSvg = "";
        var classes = "reel-verified" + (className ? " " + className : "");

        if (!isVerified) {
            return "";
        }

        if (typeof getStoredSvg === "function") {
            storedSvg = getStoredSvg("verified");
        }

        return storedSvg
            ? '<span class="' + this.escapeAttr(classes) + '" title="' + this.escapeAttr(trans("Verified")) + '">' + storedSvg + '</span>'
            : '<span class="' + this.escapeAttr(classes + " reel-verified-fallback text-primary ml-1") + '" title="' + this.escapeAttr(trans("Verified")) + '"><ion-icon name="checkmark-circle"></ion-icon></span>';
    },

    renderViewer: function (reel, context) {
        context = context || {};

        var user = reel.user || {};
        var caption = reel.caption ? '<div class="reel-caption">' + this.escape(reel.caption) + "</div>" : "";
        var verified = this.renderVerifiedBadge(user.verified);
        var sound = reel.sound ? '<div class="small text-white-50 mt-1"><ion-icon name="musical-notes-outline"></ion-icon> ' + this.escape(reel.sound.title || "") + "</div>" : "";
        var soundtrackSrc = reel.sound && reel.sound.audio_src ? reel.sound.audio_src : "";
        var ownerAction = reel.owner ? this.renderAction("reel-delete", false, "trash-outline", "") : this.renderAction("reel-report", false, "flag-outline", "");
        var muteIcon = context.isMuted ? "volume-mute-outline" : "volume-high-outline";
        var playbackIcon = context.desiredPlayback === false ? "play" : "pause";

        return '' +
            '<button type="button" class="reel-viewer-close reel-viewer-close-floating" aria-label="' + this.escapeAttr(trans("Close")) + '"><ion-icon name="close-outline"></ion-icon></button>' +
            '<button type="button" class="reel-viewer-nav reel-viewer-prev" aria-label="' + this.escapeAttr(trans("Previous")) + '"><ion-icon name="chevron-up-outline"></ion-icon></button>' +
            '<button type="button" class="reel-viewer-nav reel-viewer-next" aria-label="' + this.escapeAttr(trans("Next")) + '"><ion-icon name="chevron-down-outline"></ion-icon></button>' +
            '<div class="reel-viewer-inner">' +
                '<div class="reel-viewer-phone">' +
                    this.renderSwipePreview(context.previousReel, "prev") +
                    this.renderSwipePreview(context.nextReel, "next") +
                    '<div class="reel-swipe-stage">' +
                        '<video class="reel-viewer-video" src="' + this.escapeAttr(reel.src || "") + '" poster="' + this.escapeAttr(reel.cover || "") + '" playsinline loop autoplay preload="metadata"' + (context.isMuted ? " muted" : "") + '></video>' +
                        (soundtrackSrc ? '<audio class="reel-viewer-soundtrack" src="' + this.escapeAttr(soundtrackSrc) + '" preload="metadata" loop></audio>' : '') +
                        '<div class="reel-top-controls">' +
                            '<button type="button" class="reel-viewer-close reel-mobile-close" aria-label="' + this.escapeAttr(trans("Close")) + '"><ion-icon name="close-outline"></ion-icon></button>' +
                            '<button type="button" class="reel-play-toggle" aria-label="' + this.escapeAttr(trans("Pause")) + '"><ion-icon name="' + playbackIcon + '"></ion-icon></button>' +
                            '<button type="button" class="reel-mute" aria-label="' + this.escapeAttr(context.isMuted ? trans("Unmute") : trans("Mute")) + '"><ion-icon name="' + muteIcon + '"></ion-icon></button>' +
                        '</div>' +
                        '<div class="reel-video-loading d-none"><div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">' + this.escape(trans("Loading...")) + '</span></div></div>' +
                        '<div class="reel-video-error d-none"><ion-icon name="alert-circle-outline"></ion-icon><span>' + this.escape(trans("Could not play this reel.")) + '</span></div>' +
                        '<div class="reel-overlay-text">' +
                            '<div class="reel-user-row">' +
                                '<a href="' + this.escapeAttr(user.url || "#") + '"><img src="' + this.escapeAttr(user.photo || "") + '" alt=""></a>' +
                                '<a class="text-white text-bold" href="' + this.escapeAttr(user.url || "#") + '">@' + this.escape(user.username || "") + verified + '</a>' +
                            '</div>' +
                            caption +
                            sound +
                        '</div>' +
                        '<div class="reel-actions">' +
                            this.renderAction("reel-react", reel.reacted, "heart-outline", reel.reactions) +
                            this.renderAction("reel-comments-toggle", false, "chatbubble-outline", reel.comments) +
                            this.renderAction("reel-bookmark", reel.bookmarked, "bookmark-outline", reel.bookmarks) +
                            this.renderAction("reel-share", false, "share-social-outline", "") +
                            ownerAction +
                            '<div class="reel-action"><ion-icon name="eye-outline"></ion-icon><span class="reel-views-count">' + this.escape(this.formatCount(reel.views || 0)) + '</span></div>' +
                        '</div>' +
                        '<div class="reel-viewer-progress' + (context.canScrub ? ' is-scrubbable' : '') + '" aria-hidden="true"><span></span></div>' +
                    '</div>' +
                '</div>' +
                '<div class="reel-viewer-comments">' +
                    '<div class="reel-viewer-comments-header">' +
                        '<strong>' + this.escape(trans("Comments")) + '</strong>' +
                        '<span class="reel-comments-count">' + this.escape(this.formatCount(reel.comments || 0)) + '</span>' +
                        '<button type="button" class="reel-comments-close" aria-label="' + this.escapeAttr(trans("Close comments")) + '"><ion-icon name="close-outline"></ion-icon></button>' +
                    '</div>' +
                    '<div class="reel-comments-list small text-muted">' + this.escape(trans("Loading comments...")) + '</div>' +
                    '<form class="reel-comment-form">' +
                        '<input type="text" class="form-control form-control-sm" maxlength="1000" placeholder="' + this.escapeAttr(trans("Add a comment...")) + '">' +
                        '<button class="btn btn-sm btn-primary mb-0" type="submit">' + this.escape(trans("Send")) + '</button>' +
                    '</form>' +
                '</div>' +
            '</div>';
    },

    renderSwipePreview: function (reel, direction) {
        if (!reel) {
            return '<div class="reel-swipe-preview reel-swipe-preview-' + this.escapeAttr(direction) + '"></div>';
        }

        var cover = reel && reel.cover ? reel.cover : "";
        var image = cover ? '<img src="' + this.escapeAttr(cover) + '" alt="">' : '<div class="reel-swipe-preview-placeholder"><ion-icon name="play-outline"></ion-icon><span>' + this.escape(trans("No preview")) + '</span></div>';

        return '<div class="reel-swipe-preview reel-swipe-preview-' + this.escapeAttr(direction) + '">' + image + '</div>';
    },

    renderAction: function (className, active, icon, count) {
        var countMarkup = count === "" ? "" : '<span>' + this.escape(this.formatCount(count || 0)) + '</span>';
        var iconClass = active ? ' class="text-primary"' : "";
        return '<button type="button" class="reel-action ' + className + (active ? " is-active" : "") + '">' +
            '<ion-icon name="' + icon + '"' + iconClass + '></ion-icon>' +
            countMarkup +
        '</button>';
    },

    renderComments: function (comments) {
        if (!comments.length) {
            return this.renderEmptyCommentsState();
        }

        return comments.map(this.renderComment.bind(this)).join("");
    },

    renderComment: function (comment) {
        var user = comment.user || {};
        var verified = this.renderVerifiedBadge(user.verified);

        return '<div class="reel-comment" data-comment-id="' + this.escapeAttr(comment.id) + '">' +
            '<img src="' + this.escapeAttr(user.photo || "") + '" alt="">' +
            '<div class="reel-comment-body">' +
                '<div><a href="' + this.escapeAttr(user.url || "#") + '" class="reel-comment-author text-bold">@' + this.escape(user.username || "") + verified + '</a></div>' +
                '<div class="reel-comment-message">' + this.escape(comment.message || "") + '</div>' +
                '<div class="reel-comment-actions" data-comment-id="' + this.escapeAttr(comment.id) + '" data-user-id="' + this.escapeAttr(user.id || "") + '">' +
                    '<button type="button" class="reel-comment-action reel-comment-react' + (comment.reacted ? " is-active text-primary" : "") + '">' +
                        '<ion-icon name="heart-outline"></ion-icon><span>' + this.escape(this.formatCount(comment.reactions || 0)) + '</span>' +
                    '</button>' +
                    (!comment.owner ? '<button type="button" class="reel-comment-action reel-comment-report">' + this.escape(trans("Report")) + '</button>' : "") +
                    (comment.can_delete ? '<button type="button" class="reel-comment-action reel-comment-delete">' + this.escape(trans("Delete")) + '</button>' : "") +
                '</div>' +
            '</div>' +
        '</div>';
    },

    renderEmptyCommentsState: function () {
        return '<div class="reel-comments-empty text-muted">' + this.escape(trans("No comments yet.")) + "</div>";
    },

    formatCount: function (value) {
        value = parseInt(value || 0, 10);
        if (value >= 1000000) {
            return (value / 1000000).toFixed(value >= 10000000 ? 0 : 1).replace(/\.0$/, "") + "M";
        }
        if (value >= 1000) {
            return (value / 1000).toFixed(value >= 10000 ? 0 : 1).replace(/\.0$/, "") + "K";
        }
        return String(value);
    },

    escape: function (value) {
        return $("<div>").text(value === null ? "" : value).html();
    },

    escapeAttr: function (value) {
        return this.escape(value).replace(/"/g, "&quot;");
    }
};

window.ReelsRenderer = ReelsRenderer;
