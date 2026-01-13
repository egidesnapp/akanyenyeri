/**
 * Akanyenyeri Magazine Theme JavaScript
 * Based on Rectified Magazine Theme
 */

(function ($) {
  "use strict";

  // DOM Ready
  $(document).ready(function () {
    // Initialize Slick Carousel
    initSlickCarousel();

    // Mobile Menu Toggle
    initMobileMenu();

    // Search Toggle
    initSearchToggle();

    // Sticky Header
    initStickyHeader();

    // Go to Top Button
    initGoToTop();

    // Top Bar Toggle (Mobile)
    initTopBarToggle();

    // Set Current Date
    setCurrentDate();

    // Marquee Effect for Trending
    initMarquee();

    // Dropdown Menu Focus
    initDropdownFocus();

    // Dark Mode Toggle
    initThemeToggle();
  });

  /**
   * Dark Mode Toggle
   */
  function initThemeToggle() {
    const toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');
    const currentTheme = localStorage.getItem('theme');
    const sunIcon = document.querySelector('.fa-sun');
    const moonIcon = document.querySelector('.fa-moon');
    const sliderToggle = document.querySelector('.slider-toggle');

    if (currentTheme) {
        document.documentElement.setAttribute('data-theme', currentTheme);
    
        if (currentTheme === 'dark') {
            toggleSwitch.checked = true;
            if(sunIcon) sunIcon.style.opacity = 0;
            if(moonIcon) moonIcon.style.opacity = 1;
            if(sliderToggle) sliderToggle.style.transform = 'translateX(24px)';
        }
    }

    function switchTheme(e) {
        if (e.target.checked) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            if(sunIcon) sunIcon.style.opacity = 0;
            if(moonIcon) moonIcon.style.opacity = 1;
            if(sliderToggle) sliderToggle.style.transform = 'translateX(24px)';
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
            if(sunIcon) sunIcon.style.opacity = 1;
            if(moonIcon) moonIcon.style.opacity = 0;
            if(sliderToggle) sliderToggle.style.transform = 'translateX(0)';
        }    
    }

    toggleSwitch.addEventListener('change', switchTheme, false);
  }

  /**
   * Initialize Slick Carousel
   */
  function initSlickCarousel() {
    $(".ct-post-carousel").slick({
      dots: false,
      infinite: true,
      speed: 500,
      slidesToShow: 1,
      slidesToScroll: 1,
      autoplay: true,
      autoplaySpeed: 5000,
      arrows: true,
      prevArrow:
        '<button type="button" class="slick-prev"><i class="fa fa-chevron-left"></i></button>',
      nextArrow:
        '<button type="button" class="slick-next"><i class="fa fa-chevron-right"></i></button>',
      fade: true,
      cssEase: "linear",
    });
  }

  /**
   * Mobile Menu Toggle
   */
  function initMobileMenu() {
    $(".menu-toggle").on("click", function () {
      var $nav = $(this).closest(".rectified-magazine-header-block");
      $nav.toggleClass("toggled");

      var expanded = $(this).attr("aria-expanded") === "true";
      $(this).attr("aria-expanded", !expanded);
    });

    // Submenu toggle for mobile
    $(".menu-item-has-children > a").on("click", function (e) {
      if ($(window).width() < 768) {
        e.preventDefault();
        $(this).parent().toggleClass("focus");
      }
    });
  }

  /**
   * Search Toggle
   */
  function initSearchToggle() {
    $(".search-icon-box").on("click", function () {
      $(".top-bar-search").addClass("open");
      $('.top-bar-search input[type="search"]').focus();
    });

    $(".top-bar-search .close").on("click", function () {
      $(".top-bar-search").removeClass("open");
    });

    // Close on Escape key
    $(document).on("keyup", function (e) {
      if (e.key === "Escape") {
        $(".top-bar-search").removeClass("open");
      }
    });
  }

  /**
   * Sticky Header
   */
  function initStickyHeader() {
    var $menuContainer = $(".rectified-magazine-menu-container");
    var menuOffset = $menuContainer.length ? $menuContainer.offset().top : 0;

    $(window).on("scroll", function () {
      if ($(window).scrollTop() > menuOffset + 100) {
        $menuContainer.addClass("ct-sticky");
      } else {
        $menuContainer.removeClass("ct-sticky");
      }
    });
  }

  /**
   * Go to Top Button
   */
  function initGoToTop() {
    var $toTop = $("#toTop");

    $(window).on("scroll", function () {
      if ($(window).scrollTop() > 300) {
        $toTop.fadeIn(200);
      } else {
        $toTop.fadeOut(200);
      }
    });

    $toTop.on("click", function (e) {
      e.preventDefault();
      $("html, body").animate(
        {
          scrollTop: 0,
        },
        400,
      );
    });
  }

  /**
   * Top Bar Toggle (Mobile)
   */
  function initTopBarToggle() {
    $(".ct-show-hide-top").on("click", function (e) {
      e.preventDefault();
      $(this).find("i").toggleClass("ct-rotate");
      $(".top-bar .container-inner").slideToggle();
    });
  }

  /**
   * Set Current Date
   */
  function setCurrentDate() {
    var options = {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    };
    var today = new Date();
    $("#current-date").text(today.toLocaleDateString("en-US", options));
  }

  /**
   * Simple Marquee Effect
   */
  function initMarquee() {
    var $marquee = $(".js-marquee");
    if ($marquee.length) {
      // Clone content for seamless loop
      var content = $marquee.html();
      $marquee.append(content);
    }
  }

  /**
   * Dropdown Menu Focus for Keyboard Navigation
   */
  function initDropdownFocus() {
    $(".menu-item-has-children")
      .on("mouseenter focusin", function () {
        $(this).addClass("focus");
      })
      .on("mouseleave focusout", function () {
        $(this).removeClass("focus");
      });
  }
})(jQuery);
