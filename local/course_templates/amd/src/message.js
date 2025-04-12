define(['jquery', 'core/modal_factory'], function($, ModalFactory) {
  var trigger = $('#know_more_templates');
  ModalFactory.create({
    title: '<b>Steps to create a template</b>',
    body: '<p>1. Go to ‘Course catalogue’ from  left side navigation</br>\n\
2. Click on ‘Manage courses’ Button</br>\n\
3. In ‘Course and category management’ select a catagory to view courses</br>\n\
4. Select checkbox next to course you want to move</br>\n\
5. Select ‘Course templates’ from the dropdown of ‘Move selected courses to...’</br>\n\
6. Click Move.</br></br>\n\
Congratulations! Your newly created course can now be used as a template.</p>'
        ,
  }, trigger)
  .done(function(modal) {
    // Do what you want with your new modal.
  });
});

// $(document).ready(function(){
//   $('bs-stepper-header').click(function(){
//     $('step').removeClass("active");
//     $('#logins-part-trigger').addclass("blue")
//     $('#li1a').addClass("blue");
//     $(this).addClass("active");
// });
// });