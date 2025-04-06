define([
    'jquery'
], function($) {             
    $('.managecourses .nav-tabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });

    $('body').on('click', function (e) {
        $('[data-toggle="popover"]').each(function () {
            //the 'is' for buttons that trigger popups
            //the 'has' for icons within a button that triggers a popup 
            if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                $(this).popover('hide');
            }
        });
    });

    /* ------- Check navbar button status -------- */
    if ($(".main-header .drawer-box button").attr('aria-expanded') === "true") {
        $(".main-header .drawer-box").find('button').addClass('is-active');
    }
    /* ------ Event for change the drawer navbar style  ------ */
    $(".main-header .drawer-box button").click(function() {
        var $this = $(this);
        setTimeout(function() {
            if ($this.attr('aria-expanded') === "true") {
                $(".main-header .drawer-box").find('button').addClass('is-active');
            } else {
                $(".main-header .drawer-box").find('button').removeClass('is-active');
            }
        }, 200);
    });
    
    
    $(document).on("click","#verticalMainMenuCollapse .hamburger",function() {
        $('.mainmenu-container, .global-container, .mainMenuToggle, .global-header, #page-footer').addClass('active');
        $('.hamburger').addClass('d-none');
        $('.chevrondown-par').removeClass('d-none');
        $('.secondary-logo').addClass('d-block');
        $('.mh-logo').addClass('d-none');

    });
   
    $(document).on("click",".chevrondown",function() {
        $('.mainmenu-container, .global-container, .mainMenuToggle, .global-header, #page-footer').removeClass('active');
        $('.hamburger').removeClass('d-none');
        $('.chevrondown-par').addClass('d-none');
        $('.secondary-logo').removeClass('d-block');
        $('.mh-logo').removeClass('d-none');
        $('.cus-todo-block').removeClass('d-block');
        $('.cus-todo-block').addClass('d-none');
        $('.cus-bell-box').removeClass('d-none');
    });

    // $(document).on("click","#bellBox",function() {
    //     $('.abcd').addClass('d-none');
    // });




    $(document).on("click","#bellBox, .cus-doto",function() {
        if(!$('#verticalMainMenu, #verticalMainMenuCollapse, .mainMenuToggle').hasClass('active')){
            $('#verticalMainMenu, #verticalMainMenuCollapse, .mainMenuToggle, .mainmenu-container, .global-container, .global-header, #page-footer').addClass('active');
            $('.secondary-logo').toggleClass('d-block');
            $('.cus-bell-box').removeClass('d-block');
            $('.hamburger, .mh-logo, .chevrondown-par').toggleClass('d-none');  
        }
      //  $('.mainMenuToggle').toggleClass('active');
        $('.global-container, .cus-todo-block, .cus-todo-block').toggleClass('d-block');           
        $('.cus-bell-box').toggleClass('d-none');           
        //     $('.cus-bell-box').toggleClass('d-block'); 
    
    });

    // $(document).on("click",".cus-doto",function() {
    //     $('.cus-todo-block').removeClass('d-block');
    //     $('.cus-bell-box').removeClass('d-none');
    // });

    


    
//    $('#darkheader').floatThead({
//        position: 'absolute'
//    });
//    $('#darkheader').stickyColumn({
//    });
    
});

function goBack() {
    window.history.back();
}