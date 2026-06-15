/**
 * Paginator component - used for posts (feed+profile) pagination
 */
"use strict";
/* global app, Post, paginatorConfig, setCookie, eraseCookie, initTooltips, multiLineOverflows, Autolinker */

var PostsPaginator = {

    isFetching: false,
    nextPageUrl: '',
    prevPageUrl: '',
    currentPage: null,
    container: '',
    method: 'GET',
    fillTimer: null,

    /**
     * Initiates the component
     * @param route
     * @param container
     * @param method
     */
    init: function (route,container,method='GET') {
        PostsPaginator.nextPageUrl = route;
        PostsPaginator.prevPageUrl = paginatorConfig.prev_page_url;
        PostsPaginator.currentPage = paginatorConfig.current_page;
        PostsPaginator.container = container;
        PostsPaginator.method = method;
    },

    /**
     * Loads up reversed paginated rows
     */
    loadPreviousResults: function(){
        PostsPaginator.loadResults('prev');
        $('.reverse-paginate-btn').find('button').addClass('disabled');
    },

    /**
     * Loads (new) up paginated results
     * @param direction
     */
    loadResults: function (direction='next') {
        let url = PostsPaginator.nextPageUrl;
        if(direction === 'prev'){
            url = PostsPaginator.prevPageUrl;
        }
        if(PostsPaginator.isFetching === true || !url){
            return false;
        }
        PostsPaginator.isFetching = true;
        PostsPaginator.toggleLoadingIndicator(true);
        $.ajax({
            type: PostsPaginator.method,
            url: url,
            dataType: 'json',
            success: function(result) {
                if(result.success){
                    if(result.data.hasMore === false){
                        PostsPaginator.unbindPaginator();
                    }
                    if(direction !== 'prev'){
                        PostsPaginator.nextPageUrl = result.data.next_page_url;
                    }
                    else{
                        PostsPaginator.prevPageUrl = result.data.prev_page_url;
                        $('.reverse-paginate-btn').find('button').removeClass('disabled');
                    }

                    if(result.data.prev_page_url === null){
                        $('.reverse-paginate-btn').fadeOut("fast", function() {});
                    }

                    // Appending the items & incrementing the counter
                    PostsPaginator.appendPostResults(result.data.posts, direction);
                    PostsPaginator.isFetching = false;
                    initTooltips();
                    if(direction !== 'prev' && result.data.hasMore !== false){
                        PostsPaginator.scheduleViewportFill();
                    }
                }
                else{
                    // Handle error-ed requests
                    PostsPaginator.isFetching = false;
                }
                PostsPaginator.toggleLoadingIndicator(false);
            }
        });
    },

    /**
     * Toggles the loading indicator
     * @param loading
     */
    toggleLoadingIndicator: function(loading = false){
        if(loading === true){
            $('.posts-loading-indicator .spinner').removeClass('d-none');
        }
        else{
            $('.posts-loading-indicator .spinner').addClass('d-none');
        }
    },

    /**
     * Function that redirects to the post page, from feed implementations
     * while setting a cookie containing last feed page & selected postID
     * @param postID
     * @param post_page
     * @param url
     */
    goToPostPageKeepingNav: function(postID,post_page, url){
        if(post_page !== 1){
            setCookie('app_prev_post', postID, 365);
            setCookie('app_feed_prev_page', post_page, 365);
        }
        else{
            eraseCookie('app_prev_post');
            eraseCookie('app_feed_prev_page');
        }
        window.location.href = url;
    },

    /**
     * When navigating back from a post to the feed,
     * navigates the user to the last visisted post
     * @param postID
     */
    scrollToLastPost: function(postID){
        $('html, body').animate({
            scrollTop: parseInt($('*[data-postID="'+postID+'"]').offset().top)
        }, 300);
    },

    /**
     * Appends new posts to the feed container
     * @param posts
     * @param direction
     */
    appendPostResults: function(posts, direction = 'next'){
        // Building up the HTML array
        let htmlOut = [];
        let postIDs = [];
        $.map(posts,function (post) {
            htmlOut.push(post.html);
            postIDs.push(post.id);
        });

        // Appending the output
        if(direction === 'next'){
            $(PostsPaginator.container).append(htmlOut.join('<hr>') + '<hr>').fadeIn('slow');
        }else{
            $(PostsPaginator.container).prepend(htmlOut.join('<hr>') + '<hr>').fadeIn('slow');
        }

        // Init swiper for posts
        Post.initPostsMediaModule();
        if(app.feedDisableRightClickOnMedia === true){
            Post.disablePostsRightClick();
        }

        // Init gallery module for each post
        PostsPaginator.initPostsGalleries(postIDs);

        // Init hyperlinks (if allowed)
        PostsPaginator.initPostsHyperLinks();

        // Animate polls
        Post.animatePollResults();

        // Initing read more/less toggler based on clip property
        PostsPaginator.initDescriptionTogglers();

        $(document).trigger("posts:appended", [posts, direction]);

    },

    /**
     * Initiates the post(s) galleries
     * @param postIDs
     */
    initPostsGalleries:function(postIDs){
        $.map(postIDs,function (postID) {
            Post.initGalleryModule($('*[data-postID="'+postID+'"]'));
        });
    },

    /**
     * Globally instantiates all href links within a conversation
     */
    initPostsHyperLinks: function() {
        if(app.allow_hyperlinks) {
            $('.post-content-data p').each(function () {
                var linkedText = Autolinker.link($(this).html(), {
                    urls: {
                        schemeMatches: true,
                        wwwMatches: true,
                        tldMatches: false
                    },
                    email: false,
                    phone: false,
                    mention: false,
                    hashtag: false,
                    sanitizeHtml: false,
                    className: "",
                    truncate: {length: 64, location: 'middle'},
                    replaceFn: function (match) {
                        var tag = match.buildTag();
                        tag.setAttr('rel', 'nofollow noopener noreferrer');
                        return tag;
                    }
                });
                $(this).html(linkedText);
            });
        }
    },

    /**
     * Initiates infinite scrolling
     */
    initScrollLoad: function(){
        window.onscroll = function() {
            if (((window.innerHeight + window.scrollY + 2) * window.devicePixelRatio.toFixed(2)) >= document.body.offsetHeight * window.devicePixelRatio.toFixed(2)) {
                PostsPaginator.loadResults();
            }
        };
        PostsPaginator.scheduleViewportFill();
    },

    scheduleViewportFill: function(){
        window.clearTimeout(PostsPaginator.fillTimer);
        PostsPaginator.fillTimer = window.setTimeout(function () {
            PostsPaginator.fillViewport();
        }, 80);
    },

    fillViewport: function(){
        if(PostsPaginator.isFetching === true || !PostsPaginator.nextPageUrl){
            return;
        }

        const documentHeight = Math.max(
            document.body.scrollHeight,
            document.body.offsetHeight,
            document.documentElement.clientHeight,
            document.documentElement.scrollHeight,
            document.documentElement.offsetHeight
        );
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        if(documentHeight <= viewportHeight + 120){
            PostsPaginator.loadResults();
        }
    },

    /**
     * Unbinds the paginator infinite scrolling behaviour
     */
    unbindPaginator: function () {
        PostsPaginator.nextPageUrl = '';
        window.clearTimeout(PostsPaginator.fillTimer);
        window.onscroll = function() {};
    },

    /**
     * Instantiates the JS based read more/less
     */
    initDescriptionTogglers: function () {
        $('.post-box').each(function(key, element){
            let postID = $(element).attr('data-postID');
            if(multiLineOverflows('*[data-postID="'+postID+'"] .post-content-data')){
                $('*[data-postID="'+postID+'"]').find('.show-more-actions').removeClass('d-none');
            }
        });
    }

};
