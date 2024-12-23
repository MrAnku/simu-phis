function checkResponse(res) {
    if (res.status == 1) {
        Swal.fire(
            res.msg,
            '',
            'success'
        ).then(function () {
            window.location.href = window.location.href
        })
    } else {
        Swal.fire(
            res.msg,
            '',
            'error'
        ).then(function () {
            window.location.href = window.location.href
        })
    }
}


function relaunch_camp(campid) {
    Swal.fire({
        title: 'Are you sure?',
        text: "The previous statistics and reports of this campaign will be erased.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e6533c',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Re-Launch'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post({
                url: '/campaigns/relaunch',
                data: {
                    campid: campid
                },
                success: function (res) {

                    // console.log(res)
                    window.location.href = window.location.href;
                }
            })
        }
    })

   
}

function reschedulecampid(id) {
    $("#recampid").val(id);
}

function deletecampaign(campid) {

    Swal.fire({
        title: 'Are you sure?',
        text: "Are you sure that you want to delete this Campaign?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e6533c',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post({
                url: '/campaigns/delete',
                data: {
                    campid: campid
                },
                success: function (res) {

                    // console.log(res)
                    window.location.href = window.location.href;
                }
            })
        }
    })

  
}

//campaign type toggling
$("#campaign_type").on('change', function () {

    var type = $(this).val();
    if (type == 'Phishing') {

        $("#pm_step").show()
        $("#tm_step").hide()

        $("#pm_step_form").addClass('included');
        $("#pm_step_form input[type='radio']").addClass('required');

        $("#tm_step_form").removeClass('included');
        $("#tm_step_form input[type='radio']").removeClass('required');

    } else if (type == 'Training') {

        $("#pm_step").hide()
        $("#tm_step").show()

        $("#pm_step_form").removeClass('included');

        $("#tm_step_form").addClass('included');

    } else if (type == 'Phishing & Training') {

        $("#pm_step").show()
        $("#tm_step").show()

        $("#pm_step_form").addClass('included');
        $("#tm_step_form").addClass('included');

    }
})



$(document).ready(function () {

    var current_fs, next_fs, previous_fs; //fieldsets
    var opacity;
    var days_until_due = '';
    $(".next").click(function () {

        current_fs = $(this).parent();
        next_fs = $(this).parent().next();

        if (!next_fs.hasClass('included')) {
            next_fs = next_fs.next();
        }

        var allFilled = true;
        current_fs.find('.required').each(function () {
            if ($(this).val() === '') {
                allFilled = false;
                return false; // Exit the loop early if any field is empty
            }

        });

        if (current_fs.attr('id') === 'pm_step_form') {
            // Check if at least one radio button is checked
            days_until_due = '';
            var radioChecked = false;
            current_fs.find('input[type="radio"][name="phish_material"]').each(function () {
                if ($(this).is(':checked')) {
                    radioChecked = true;
                    return false; // Exit the loop early if any radio button is checked
                }
            });

            if (!radioChecked) {
                alert('Please select phishing material');
                return; // Stop further execution
            }
        }

        if (current_fs.attr('id') === 'tm_step_form') {
            // Check if at least one radio button is checked
            var radioChecked = false;
            current_fs.find('input[type="radio"][name="training_module"]').each(function () {
                if ($(this).is(':checked')) {
                    radioChecked = true;
                    return false; // Exit the loop early if any radio button is checked
                }
            });

             days_until_due = $('#days_until_due').val();
            if (days_until_due == '') {
                alert('Please enter days until due');
                return false; // Stop further execution
            }
            if(days_until_due < 1){
                alert('Days until due must be greater than 0');
                return false; // Stop further execution
            }

            if (!radioChecked) {
                alert('Please select training module');
                return; // Stop further execution
            }
        }




        if (allFilled) {
            //Add Class Active
            $("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active");

            //show the next fieldset
            next_fs.show();
            //hide the current fieldset with style
            current_fs.animate({
                opacity: 0
            }, {
                step: function (now) {
                    // for making fielset appear animation
                    opacity = 1 - now;

                    current_fs.css({
                        'display': 'none',
                        'position': 'relative'
                    });
                    next_fs.css({
                        'opacity': opacity
                    });
                },
                duration: 600
            });

            if ($(this).hasClass('last-step')) {
                // The targeted element has class "last-step"
                reviewFormData();
            }
        } else {
            // Alert or inform the user that some required fields are empty
            alert('Please fill all required fields!');
        }




    });

    $(".previous").click(function () {

        current_fs = $(this).parent();
        previous_fs = $(this).parent().prev();

        if (!previous_fs.hasClass('included')) {
            previous_fs = previous_fs.prev();
        }

        //Remove class active
        $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");

        //show the previous fieldset
        previous_fs.show();

        //hide the current fieldset with style
        current_fs.animate({
            opacity: 0
        }, {
            step: function (now) {
                // for making fielset appear animation
                opacity = 1 - now;

                current_fs.css({
                    'display': 'none',
                    'position': 'relative'
                });
                previous_fs.css({
                    'opacity': opacity
                });
            },
            duration: 600
        });
    });

    $('.radio-group .radio').click(function () {
        $(this).parent().find('.radio').removeClass('selected');
        $(this).addClass('selected');
    });

    $(".submit").click(function () {
        return false;
    })

    var dataToBeSaved = {};
    function reviewFormData() {

        function launch_time() {
            var currentDate = new Date();
            var formattedDate = formatDate(currentDate);
            return formattedDate;
        }
        var training_module = '';
        var trainingLang = '';
        var trainingType = '';
        var phishing_email = '';
        var phishing_lang = '';

        if (campaign_type.value == "Phishing") {
            phishing_email = $("input[name='phish_material']:checked").val();
            phishing_lang = email_lang.value;
        }

        if (campaign_type.value == "Training") {
            training_module = $("input[name='training_module']:checked").val();
            trainingLang = training_lang.value;
            trainingType = training_type.value;
        }
        if (campaign_type.value == "Phishing & Training") {
            phishing_email = $("input[name='phish_material']:checked").val();
            phishing_lang = email_lang.value;
            training_module = $("input[name='training_module']:checked").val();
            trainingLang = training_lang.value;
            trainingType = training_type.value;
        }
        var formData = {

            camp_name: camp_name.value,
            campaign_type: campaign_type.value,
            users_group: users_group.value,
            email_lang: phishing_lang,
            phish_material: phishing_email,
            trainingLang: trainingLang,
            training_mod: training_module,
            training_type: trainingType,
            schType: $("input[name='schType']:checked").val(),
            schBetRange: schBetRange.value,
            schTimeStart: schTimeStart.value,
            schTimeEnd: schTimeEnd.value,
            schTimeZone: schTimeZone.value,
            emailFreq: $("input[name='emailFreq']:checked").val(),
            expire_after: expire_after.value,
            days_until_due: days_until_due,

            launch_time: launch_time()

        };

        revCampName.value = formData.camp_name ?? '--';
        revCampType.value = $("#campaign_type option:selected").text() ?? '--';
        revEmpGroup.value = $("#users_group option:selected").text().trim() ?? '--';
        revEmailLang.value = $("#email_lang option:selected").text() ?? '--';
        revPhishmat.value = $("input[name='phish_material']:checked").data('phishmatname') ?? '--';
        revTrainingLang.value = $("#training_lang option:selected").text() ?? '--';
        revTrainingMod.value = $("input[name='training_module']:checked").data('trainingname') ?? '--';
        revTrainingType.value = $("#training_type option:selected").text().trim() ?? '--';
        revCampDelivery.value = $("input[name='schType']:checked").data('val') ?? '--';
        revBtwDays.value = formData.schBetRange ?? '--';
        revSchTimeStart.value = formData.schTimeStart ?? '--';
        revSchTimeEnd.value = formData.schTimeEnd ?? '--';
        revSchTimeZone.value = formData.schTimeZone ?? '--';
        revEmailFreq.value = $("input[name='emailFreq']:checked").data('val') ?? '--';
        revExpAfter.value = formData.expire_after ?? '--';
        revDays_until_due.value = formData.days_until_due ?? '--';

        if (formData.campaign_type == 'Phishing') {
            $("#revTrainingLang").parent().parent().hide();
            $("#revTrainingMod").parent().parent().hide();
            $("#revTrainingType").parent().parent().hide();

            $("#revPhishmat").parent().parent().show();
            $("#revEmailLang").parent().parent().show();
            $("#revDays_until_due").parent().parent().hide();
        }

        if (formData.campaign_type == 'Training') {
            $("#revPhishmat").parent().parent().hide();
            $("#revEmailLang").parent().parent().hide();

            $("#revTrainingLang").parent().parent().show();
            $("#revTrainingMod").parent().parent().show();
            $("#revTrainingType").parent().parent().show();
            $("#revDays_until_due").parent().parent().show();
        }

        if (formData.schType == 'immediately') {
            $("#revBtwDays").parent().parent().hide();
            $("#revSchTimeZone").parent().parent().hide();
            $("#revBtwTime").hide();


        }

        if (formData.schType == 'scheduled') {
            $("#revBtwDays").parent().parent().show();
            $("#revSchTimeZone").parent().parent().show();
            $("#revBtwTime").show();


        }
        if(formData.expire_after == ""){
            $("#revExpAfter").parent().parent().hide();
        }else{
            $("#revExpAfter").parent().parent().show();
        }

        // Output JSON
        console.log(formData);

        dataToBeSaved = formData;
    }

  

    $('#createCampaign').click(function (e) {
        e.preventDefault();

         console.log(dataToBeSaved);
        $.post({
            url: '/campaigns/create',
            data: dataToBeSaved,
            success: function (res) {
                checkResponse(res);
                // console.log(res);
            }
        })
    })



});

function formatDate(date) {
    var month = (date.getMonth() + 1).toString().padStart(2, '0'); // Get month with leading zero
    var day = date.getDate().toString().padStart(2, '0'); // Get day with leading zero
    var year = date.getFullYear(); // Get full year
    var hours = date.getHours().toString().padStart(2, '0'); // Get hours with leading zero
    var minutes = date.getMinutes().toString().padStart(2, '0'); // Get minutes with leading zero
    return month + '/' + day + '/' + year + ' ' + hours + ':' + minutes;
}



//handling imediate and schedule btn
$("#imediateLabelBtn").click(function () {
    $("#dvSchedule2").addClass("d-none");
    $("#email_frequency").removeClass("d-none");
    // var currentDate = new Date();
    // var formattedDate = formatDate(currentDate);
    // $("#launch_time").val(formattedDate);
})
$("#scheduleLabelBtn").click(function () {
    $("#dvSchedule2").removeClass("d-none");
    $("#email_frequency").removeClass("d-none");
})

$("#scheduleLLabelBtn").click(function () {
    $("#dvSchedule2").addClass("d-none");
    $("#email_frequency").addClass("d-none")
})

$('label[for="foneoff"]').click(function () {
    $("#exp_after").addClass("d-none");
})

$('label[for="fmonthly"]').click(function () {
    $("#exp_after").removeClass("d-none");
})
$('label[for="fweekly"]').click(function () {
    $("#exp_after").removeClass("d-none");
})
$('label[for="fquaterly"]').click(function () {
    $("#exp_after").removeClass("d-none");
})

//handling imediate and schedule btn
$("#rimediateLabelBtn").click(function () {
    $("#rdvSchedule2").addClass("d-none");
    $("#remail_frequency").removeClass("d-none");
    // var currentDate = new Date();
    // var formattedDate = formatDate(currentDate);
    // $("#launch_time").val(formattedDate);
})
$("#rscheduleLabelBtn").click(function () {
    $("#rdvSchedule2").removeClass("d-none");
    $("#remail_frequency").removeClass("d-none");
})

$("#rscheduleLLabelBtn").click(function () {
    $("#rdvSchedule2").addClass("d-none");
    $("#remail_frequency").addClass("d-none")
})

$('label[for="rfoneoff"]').click(function () {
    $("#rexp_after").addClass("d-none");
})

$('label[for="rfmonthly"]').click(function () {
    $("#rexp_after").removeClass("d-none");
})
$('label[for="rfweekly"]').click(function () {
    $("#rexp_after").removeClass("d-none");
})
$('label[for="rfquaterly"]').click(function () {
    $("#rexp_after").removeClass("d-none");
})

// $('#rescheduleBtn').on('click', function (e) {
//     e.preventDefault()
//     var formData = $('#rescheduleForm').serializeArray();
//     var data = {};
//     $.each(formData, function () {
//         if (data[this.name]) {
//             if (!data[this.name].push) {
//                 data[this.name] = [data[this.name]];
//             }
//             data[this.name].push(this.value || '');
//         } else {
//             data[this.name] = this.value || '';
//         }
//     });
    // Display the collected data in the console (for demonstration purposes)
    // console.log(data);

    // $.post({
    //     url: 'campaigns.php?reschduleCamp=1',
    //     data: data,
    //     success: function (response) {
    //         //  console.log(response);
    //         // window.location.reload()
    //         window.location.href = window.location.href;
    //     }
    // })

    // Further processing of data can be done here, e.g., sending it to the server via AJAX
// });



// Event listener for input field change
$('#templateSearch').on('input', function () {
    var searchValue = $(this).val().toLowerCase(); // Get the search value and convert it to lowercase

    // Loop through each template card
    $('.email_templates').each(function () {
        var templateName = $(this).find('.fw-semibold').text().toLowerCase(); // Get the template name and convert it to lowercase

        // If the template name contains the search value, show the card; otherwise, hide it
        if (templateName.includes(searchValue)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
});

// Event listener for input field change
$('#t_moduleSearch').on('input', function () {
    var searchValue = $(this).val().toLowerCase(); // Get the search value and convert it to lowercase

    // Loop through each template card
    $('.t_modules').each(function () {
        var templateName = $(this).find('.fw-semibold').text().toLowerCase(); // Get the template name and convert it to lowercase

        // If the template name contains the search value, show the card; otherwise, hide it
        if (templateName.includes(searchValue)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
});







$('#datatable-basic').DataTable({
    language: {
        searchPlaceholder: 'Search...',
        sSearch: '',
    },
    "pageLength": 10,
    // scrollX: true
});
