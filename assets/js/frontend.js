/**
 * WP Resource Hub - Frontend JavaScript
 *
 * @package WPResourceHub
 * @since   1.0.0
 */

(function ($) {
  "use strict";

  /**
   * Table of Contents Handler
   */
  var TableOfContents = {
    /**
     * Initialize TOC functionality.
     */
    init: function () {
      this.$toc = $(".wprh-toc");

      if (!this.$toc.length) {
        return;
      }

      this.bindEvents();
      this.initSmoothScroll();
      this.initActiveTracking();
    },

    /**
     * Bind event handlers.
     */
    bindEvents: function () {
      // Toggle TOC on mobile.
      this.$toc.find(".wprh-toc-title").on("click", function () {
        $(this).parent().toggleClass("wprh-toc-collapsed");
      });
    },

    /**
     * Initialize smooth scrolling for TOC links.
     */
    initSmoothScroll: function () {
      this.$toc.find("a").on("click", function (e) {
        var targetId = $(this).attr("href");

        if (targetId && targetId.charAt(0) === "#") {
          var $target = $(targetId);

          if ($target.length) {
            e.preventDefault();

            $("html, body").animate(
              {
                scrollTop: $target.offset().top - 80,
              },
              300,
            );

            // Update URL hash.
            if (history.pushState) {
              history.pushState(null, null, targetId);
            }
          }
        }
      });
    },

    /**
     * Initialize active heading tracking.
     */
    initActiveTracking: function () {
      var self = this;
      var $headings = $(
        ".wprh-content-body h2, .wprh-content-body h3, .wprh-content-body h4",
      ).filter("[id]");

      if (!$headings.length) {
        return;
      }

      $(window).on("scroll", function () {
        var scrollTop = $(window).scrollTop();
        var activeId = "";

        $headings.each(function () {
          var $heading = $(this);
          var headingTop = $heading.offset().top - 100;

          if (scrollTop >= headingTop) {
            activeId = $heading.attr("id");
          }
        });

        // Update active class.
        self.$toc.find("a").removeClass("wprh-toc-active");

        if (activeId) {
          self.$toc
            .find('a[href="#' + activeId + '"]')
            .addClass("wprh-toc-active");
        }
      });
    },
  };

  /**
   * Video Handler
   */
  var VideoHandler = {
    /**
     * Initialize video functionality.
     */
    init: function () {
      this.initLazyLoading();
    },

    /**
     * Initialize lazy loading for video iframes.
     */
    initLazyLoading: function () {
      var $videos = $(".wprh-video-wrapper iframe[data-src]");

      if (!$videos.length) {
        return;
      }

      // Use Intersection Observer if available.
      if ("IntersectionObserver" in window) {
        var observer = new IntersectionObserver(
          function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) {
                var $iframe = $(entry.target);
                $iframe.attr("src", $iframe.data("src"));
                observer.unobserve(entry.target);
              }
            });
          },
          {
            rootMargin: "200px",
          },
        );

        $videos.each(function () {
          observer.observe(this);
        });
      } else {
        // Fallback: load all videos immediately.
        $videos.each(function () {
          var $iframe = $(this);
          $iframe.attr("src", $iframe.data("src"));
        });
      }
    },
  };

  /**
   * External Link Handler
   */
  var ExternalLinkHandler = {
    /**
     * Initialize external link functionality.
     */
    init: function () {
      this.addExternalIndicators();
    },

    /**
     * Add indicators to external links in content.
     */
    addExternalIndicators: function () {
      $('.wprh-content-body a[target="_blank"]').each(function () {
        var $link = $(this);

        // Skip if already has indicator.
        if ($link.find(".wprh-external-indicator").length) {
          return;
        }

        // Add visual indicator.
        $link.append(
          ' <span class="wprh-external-indicator" aria-hidden="true">↗</span>',
        );

        // Add screen reader text.
        $link.append(
          '<span class="screen-reader-text">(opens in new tab)</span>',
        );
      });
    },
  };

  /**
   * Copy Link Handler
   */
  var CopyLinkHandler = {
    /**
     * Initialize copy link functionality.
     */
    init: function () {
      this.bindEvents();
    },

    /**
     * Bind event handlers.
     */
    bindEvents: function () {
      $(document).on("click", ".wprh-copy-link", this.onCopyClick.bind(this));
    },

    /**
     * Handle copy button click.
     *
     * @param {Event} e Click event.
     */
    onCopyClick: function (e) {
      e.preventDefault();

      var $button = $(e.currentTarget);
      var url = $button.data("url") || window.location.href;

      this.copyToClipboard(url).then(function () {
        var originalText = $button.text();
        $button.text("Copied!");

        setTimeout(function () {
          $button.text(originalText);
        }, 2000);
      });
    },

    /**
     * Copy text to clipboard.
     *
     * @param {string} text Text to copy.
     * @return {Promise}
     */
    copyToClipboard: function (text) {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        return navigator.clipboard.writeText(text);
      }

      // Fallback for older browsers.
      return new Promise(function (resolve, reject) {
        var textarea = document.createElement("textarea");
        textarea.value = text;
        textarea.style.position = "fixed";
        textarea.style.opacity = "0";
        document.body.appendChild(textarea);
        textarea.select();

        try {
          document.execCommand("copy");
          resolve();
        } catch (err) {
          reject(err);
        }

        document.body.removeChild(textarea);
      });
    },
  };

  /**
   * Reading Progress Handler
   */
  var ReadingProgress = {
    /**
     * Initialize reading progress functionality.
     */
    init: function () {
      this.$content = $(".wprh-resource-internal-content .wprh-content-body");

      if (!this.$content.length) {
        return;
      }

      this.createProgressBar();
      this.bindEvents();
    },

    /**
     * Create progress bar element.
     */
    createProgressBar: function () {
      this.$progressBar = $(
        '<div class="wprh-reading-progress"><div class="wprh-reading-progress-bar"></div></div>',
      );
      $("body").prepend(this.$progressBar);
    },

    /**
     * Bind event handlers.
     */
    bindEvents: function () {
      var self = this;

      $(window).on("scroll", function () {
        self.updateProgress();
      });
    },

    /**
     * Update progress bar.
     */
    updateProgress: function () {
      var contentTop = this.$content.offset().top;
      var contentHeight = this.$content.outerHeight();
      var windowHeight = $(window).height();
      var scrollTop = $(window).scrollTop();

      var scrollableHeight = contentHeight - windowHeight;
      var scrolledInContent = scrollTop - contentTop + windowHeight;

      var progress = Math.max(
        0,
        Math.min(100, (scrolledInContent / scrollableHeight) * 100),
      );

      this.$progressBar
        .find(".wprh-reading-progress-bar")
        .css("width", progress + "%");
    },
  };

  /**
   * Video Lightbox Handler
   */
  var VideoLightbox = {
    /**
     * Initialize lightbox functionality.
     */
    init: function () {
      this.$lightbox = $("#wprh-video-lightbox");

      if (!this.$lightbox.length) {
        return;
      }

      this.$overlay = this.$lightbox.find(".wprh-lightbox-overlay");
      this.$close = this.$lightbox.find(".wprh-lightbox-close");
      this.$iframe = $("#wprh-lightbox-iframe");

      this.bindEvents();
    },

    /**
     * Bind event handlers.
     */
    bindEvents: function () {
      var self = this;

      // Play button click.
      $(document).on("click", ".wprh-play-button", function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $button = $(this);
        var $videoCard = $button.closest(".wprh-video-card");
        var videoUrl = $videoCard.data("video-url");
        var videoTitle = $videoCard.data("video-title");

        if (videoUrl) {
          self.openLightbox(videoUrl, videoTitle);
        }
      });

      // Entire video card click (when lightbox mode is enabled).
      $(document).on("click", ".wprh-lightbox-enabled", function (e) {
        // Don't trigger if clicking on taxonomy pills.
        if ($(e.target).closest(".wprh-card-pill").length) {
          return;
        }

        e.preventDefault();
        var $card = $(this);
        var $videoCard = $card.find(".wprh-video-card");
        var videoUrl = $videoCard.data("video-url");
        var videoTitle = $videoCard.data("video-title");

        if (videoUrl) {
          self.openLightbox(videoUrl, videoTitle);
        }
      });

      // Close button click.
      this.$close.on("click", function (e) {
        e.preventDefault();
        self.closeLightbox();
      });

      // Overlay click.
      this.$overlay.on("click", function () {
        self.closeLightbox();
      });

      // ESC key press.
      $(document).on("keydown", function (e) {
        if (e.keyCode === 27 && self.$lightbox.is(":visible")) {
          self.closeLightbox();
        }
      });
    },

    /**
     * Open lightbox with video.
     *
     * @param {string} videoUrl Video embed URL.
     * @param {string} videoTitle Video title.
     */
    openLightbox: function (videoUrl, videoTitle) {
      // Add autoplay to URL.
      var separator = videoUrl.indexOf("?") !== -1 ? "&" : "?";
      var autoplayUrl = videoUrl + separator + "autoplay=1";

      this.$iframe.attr("src", autoplayUrl);
      this.$iframe.attr("title", videoTitle);
      this.$lightbox.fadeIn(300);
      $("body").addClass("wprh-lightbox-open").css("overflow", "hidden");
    },

    /**
     * Close lightbox and stop video.
     */
    closeLightbox: function () {
      this.$lightbox.fadeOut(300);
      this.$iframe.attr("src", "");
      $("body").removeClass("wprh-lightbox-open").css("overflow", "");
    },
  };

  /**
   * Resource Filter Handler
   */
  var ResourceFilter = {
    /**
     * Initialize filter functionality.
     */
    init: function () {
      this.$container = $(".wprh-resources-container");

      if (!this.$container.length) {
        return;
      }

      this.bindEvents();
    },

    /**
     * Bind event handlers.
     */
    bindEvents: function () {
      var self = this;

      // Filter dropdown change.
      $(document).on("change", ".wprh-filter-select", function () {
        self.applyFilters();
      });

      // Search input with debounce.
      var searchTimeout;
      $(document).on("input", ".wprh-search-input", function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
          self.applyFilters();
        }, 500);
      });

      // Layout toggle.
      $(document).on("click", ".wprh-layout-btn", function () {
        var $btn = $(this);
        var layout = $btn.data("layout");
        var $container = $btn.closest(".wprh-resources-container");
        var atts = $container.data("atts");

        if (atts.layout === layout) {
          return;
        }

        atts.layout = layout;
        $container.data("atts", atts);
        $btn.siblings(".wprh-layout-btn").removeClass("is-active");
        $btn.addClass("is-active");

        // Update grid class.
        var $grid = $container.find(".wprh-resources-grid");
        $grid.removeClass("wprh-layout-grid wprh-layout-list").addClass("wprh-layout-" + layout);

        self.applyFilters();
      });

      // Pagination buttons.
      $(document).on("click", ".wprh-page-btn", function (e) {
        e.preventDefault();

        if ($(this).prop("disabled")) {
          return;
        }

        var $pagination = $(this).closest(".wprh-pagination");
        var current = parseInt($pagination.data("current"));
        var total = parseInt($pagination.data("total"));

        if ($(this).hasClass("wprh-prev") && current > 1) {
          self.loadPage(current - 1);
        } else if ($(this).hasClass("wprh-next") && current < total) {
          self.loadPage(current + 1);
        }
      });
    },

    /**
     * Apply current filters.
     */
    applyFilters: function () {
      this.loadPage(1);
    },

    /**
     * Load a specific page with current filters.
     *
     * @param {number} page Page number.
     */
    loadPage: function (page) {
      var self = this;
      var $container = this.$container;
      var $wrapper = $container.find(".wprh-resources-grid-wrapper");
      var atts = $container.data("atts");

      // Get current filter values.
      var filters = {
        type:
          $container.find('.wprh-filter-select[data-filter="type"]').val() ||
          "",
        topic:
          $container.find('.wprh-filter-select[data-filter="topic"]').val() ||
          "",
        audience:
          $container
            .find('.wprh-filter-select[data-filter="audience"]')
            .val() || "",
        duration:
          $container
            .find('.wprh-filter-select[data-filter="duration"]')
            .val() || "",
        sort:
          $container.find('.wprh-filter-select[data-filter="sort"]').val() ||
          "",
        search: $container.find(".wprh-search-input").val() || "",
      };

      // Show loading state.
      $wrapper.addClass("wprh-loading");
      $container.find(".wprh-resources-loading").show();

      // Make AJAX request.
      $.ajax({
        url: wprhFrontend.ajaxUrl,
        type: "POST",
        data: {
          action: "wprh_filter_resources",
          nonce: wprhFrontend.nonce,
          atts: JSON.stringify(atts),
          type: filters.type,
          topic: filters.topic,
          audience: filters.audience,
          duration: filters.duration,
          sort: filters.sort,
          search: filters.search,
          paged: page,
        },
        success: function (response) {
          if (response.success) {
            // Update grid HTML.
            $wrapper
              .find(".wprh-resources-grid")
              .replaceWith(response.data.html);

            // Update pagination.
            if (response.data.max_pages > 1) {
              var $pagination = $container.find(".wprh-resources-pagination");
              if ($pagination.length) {
                $pagination.html(response.data.pagination);
              } else {
                $wrapper.after(
                  '<div class="wprh-resources-pagination">' +
                    response.data.pagination +
                    "</div>",
                );
              }
            } else {
              $container.find(".wprh-resources-pagination").remove();
            }

            // Scroll to top of results.
            $("html, body").animate(
              {
                scrollTop: $container.offset().top - 100,
              },
              300,
            );
          }
        },
        error: function () {
          alert(wprhFrontend.i18n.error);
        },
        complete: function () {
          $wrapper.removeClass("wprh-loading");
          $container.find(".wprh-resources-loading").hide();
        },
      });
    },
  };

  /**
   * Collection Accordion Handler
   */
  var CollectionAccordion = {
    /**
     * Initialize accordion functionality.
     */
    init: function () {
      this.bindEvents();
    },

    /**
     * Bind event handlers.
     */
    bindEvents: function () {
      var self = this;

      // Collection-level accordion trigger click
      $(document).on(
        "click",
        ".wprh-collection-accordion-trigger",
        function (e) {
          e.preventDefault();
          self.toggleCollectionAccordion($(this));
        },
      );

      // Resource-level accordion trigger click
      $(document).on("click", ".wprh-accordion-trigger", function (e) {
        e.preventDefault();
        e.stopPropagation();
        self.toggleResourceAccordion($(this));
      });

      // Keyboard navigation for collection-level accordion
      $(document).on(
        "keydown",
        ".wprh-collection-accordion-trigger",
        function (e) {
          if (e.keyCode === 13 || e.keyCode === 32) {
            e.preventDefault();
            self.toggleCollectionAccordion($(this));
          }
        },
      );

      // Keyboard navigation for resource-level accordion
      $(document).on("keydown", ".wprh-accordion-trigger", function (e) {
        if (e.keyCode === 13 || e.keyCode === 32) {
          e.preventDefault();
          e.stopPropagation();
          self.toggleResourceAccordion($(this));
        }
      });
    },

    /**
     * Toggle collection-level accordion open/closed.
     *
     * @param {jQuery} $trigger The trigger button element.
     */
    toggleCollectionAccordion: function ($trigger) {
      var $item = $trigger.closest(".wprh-collection-accordion-item");
      var $content = $item.find(".wprh-collection-accordion-content");
      var isOpen = $item.hasClass("is-open");

      if (isOpen) {
        // Close the collection accordion
        $trigger.attr("aria-expanded", "false");
        $item.removeClass("is-open");
        $content.css("max-height", "0");
      } else {
        // Open the collection accordion
        $trigger.attr("aria-expanded", "true");
        $item.addClass("is-open");

        // Set a very large max-height to allow for expansion
        $content.css("max-height", "50000px");
      }
    },

    /**
     * Toggle resource-level accordion open/closed.
     *
     * @param {jQuery} $trigger The trigger button element.
     */
    toggleResourceAccordion: function ($trigger) {
      var self = this;
      var $item = $trigger.closest(".wprh-accordion-item");
      var $content = $item.children(".wprh-accordion-content");
      var isOpen = $item.hasClass("is-open");

      if (isOpen) {
        // Close the resource accordion
        $trigger.attr("aria-expanded", "false");
        $item.removeClass("is-open");
        $content.css("max-height", "0");
      } else {
        // Open the resource accordion
        $trigger.attr("aria-expanded", "true");
        $item.addClass("is-open");

        // Nested groups use large max-height to accommodate expandable children
        if ($item.hasClass("wprh-nested-group")) {
          $content.css("max-height", "50000px");
        } else {
          // Calculate actual content height for smooth animation
          var contentHeight = $content
            .children(".wprh-accordion-inner")
            .outerHeight(true);
          $content.css("max-height", contentHeight + 50 + "px");
        }
      }

      // Update parent collection height after transition
      setTimeout(function () {
        self.updateCollectionHeight($item);
      }, 350); // Match CSS transition time
    },

    /**
     * Update the parent collection accordion height.
     *
     * @param {jQuery} $resourceItem The resource item that was toggled.
     */
    updateCollectionHeight: function ($resourceItem) {
      var $collectionItem = $resourceItem.closest(
        ".wprh-collection-accordion-item",
      );

      if ($collectionItem.length && $collectionItem.hasClass("is-open")) {
        var $collectionContent = $collectionItem.find(
          ".wprh-collection-accordion-content",
        );

        // Set to a very large value to ensure all content is visible
        $collectionContent.css("max-height", "50000px");
      }
    },
  };

  /**
   * Initialize all modules on document ready.
   */
  $(document).ready(function () {
    TableOfContents.init();
    VideoHandler.init();
    ExternalLinkHandler.init();
    CopyLinkHandler.init();
    VideoLightbox.init();
    ResourceFilter.init();
    CollectionAccordion.init();

    // Only initialize reading progress on internal content resources.
    if ($("body").hasClass("wprh-resource-type-internal-content")) {
      ReadingProgress.init();
    }
  });
})(jQuery);
