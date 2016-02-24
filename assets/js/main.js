var plans;
var curr;
var intToDay = {
    0: 'M',
    1: 'T',
    2: 'W',
    3: 'Th',
    4: 'F',
    5: 'Sa',
    6: 'Su'
};
$(document).ready(function () {

    var cInsert = $("#course-insert");
    var cList = $('#course-list');
    var pagination = $('.pagination');
    var breadcrumb = $('#breadcrumb');
    // popup for share buttons
    $('a.popup').on('click', function () {
        var newwindow = window.open($(this).attr('href'), '', 'height=400,width=600');
        if (window.focus) {
            newwindow.focus()
        }
        return false;
    });

    $('#show-duplicates').checkboxpicker({
        html: true,
        offLabel: '<span class="glyphicon glyphicon-remove">',
        onLabel: '<span class="glyphicon glyphicon-ok">'
    });

    // auto-completion for course names
    var availableTags = process("getAvailableCourses", 1);
    var availableTagsWithSections = availableTags[1];
    var currWithSections = false;
    availableTags = availableTags[0];
    cInsert.autocomplete({
        source: function (request, response) {
            var results = $.ui.autocomplete.filter(availableTags, request.term);
            response(results.slice(0, 10));
        }
    }).keyup(function (e) {
        if (e.which === 13) {
            $(".ui-menu-item").hide();
        }
    });

    cInsert.keydown(function (event) {
        if (event.keyCode == 13) {
            $("#add-course").click();
        } else if (event.keyCode == 190 && currWithSections === false) {
            cInsert.autocomplete({
                source: function (request, response) {
                    var results = $.ui.autocomplete.filter(availableTagsWithSections, request.term);
                    response(results.slice(0, 10));
                }
            });
            currWithSections = true;
        } else if (cInsert.val().indexOf(".") == -1 && currWithSections === true) {
            cInsert.autocomplete({
                source: function (request, response) {
                    var results = $.ui.autocomplete.filter(availableTags, request.term);
                    response(results.slice(0, 10));
                }
            });
            currWithSections = false;
        }
    });

    cInsert.keyup(function(event) {
        cInsert.val(cInsert.val().turkishToUpper());
    });

    String.prototype.turkishToUpper = function () {
        var string = this;
        var letters = {"i": "I", "ş": "S", "ğ": "G", "ü": "U", "ö": "O", "ç": "C", "ı": "I"};
        string = string.replace(/[iışğüçö]+/g, function (letter) {
            return letters[letter];
        });
        return string.toUpperCase();
    };

    Object.getKeyByValue = function (obj, value) {
        for (var key in obj) {
            if (obj.hasOwnProperty(key)) {
                if (obj[key] == value) return key;
            }
        }
    };

    Object.size = function (obj) {
        var size = 0, key;
        for (key in obj) {
            if (obj.hasOwnProperty(key)) size++;
        }
        return size;
    };

    //  add course button click function
    $('button#add-course').on('click', function () {
        var course = $('input[name=course-insert]').val().turkishToUpper();
        if (course.length == 0) {
            alert(COURSE_EMPTY);
            return;
        } else if (availableTags.indexOf(course) == -1 && availableTagsWithSections.indexOf(course) == -1) {
            alert(COURSE_NOT_VALID);
            return;
        } else if ($('#course-list:contains("' + (course.indexOf(".") !== -1 ? course.split(".")[0] : course) + '")').length !== 0) {
            alert(COURSE_ALREADY_ADDED);
            return;
        }
        if ('.nothing-message') {
            $('.nothing-message').hide('slide', {direction: 'left'}, 300)
        }
        var newCourse = '<li>' + '<p>' + course + '</p>' + '</li>';
        $('#course-list').append(newCourse);
        $('input').val('');
        $('#controls').fadeIn();
    });

    // load the plan
    $('button#find-best-plan').on('click', function () {
        curr = 0;
        var courseList = [];
        var freeHours = [];
        cList.find('li:not(.course-disabled)').find('p').each(function (index, el) {
            courseList.push(el.innerHTML);
        });
        if (courseList.length == 0) return;

        $('.schedule-rows').find('.time-slot').each(function (index, el) {
            if (el.getAttribute('data-selected') == 'selected')
                freeHours.push(intToDay[el.getAttribute('data-day')] + el.getAttribute('data-time').split(":")[0].replace(/^0/, ''));
        });

        var result = process("findBestPlan", {
            "courseList": courseList,
            "freeHours": freeHours,
            "showDuplicates": $("#show-duplicates").prop('checked')
        });

        var conflict = result["conflict"];
        plans = result["plans"];
        var plansSize = Object.size(plans);

        pagination.html('');
        pagination.append('<li><a id="prev">«</a></li>');
        for (var i = 1; i <= plansSize; i++) {
            pagination.append('<li><a class="load-plan" data-item-id="' + (i - 1) + '">' + i + '</a></li>');
        }
        pagination.append('<li><a id="next">»</a></li>');

        //noinspection JSValidateTypes
        $('.load-plan[data-item-id="' + curr + '"]').parent().addClass('active');
        if (breadcrumb.hasClass('hidden'))
            breadcrumb.removeClass('hidden');

        loadPlan(curr);
        $('#possible-schedules').html(plansSize);
        $('#conflicted-hours').html(conflict);
    });

    cList.on('click', 'li', function () {
        $(this).toggleClass('course-disabled');
    });

    // double click to remove
    cList.on('dblclick', 'li', function () {
        $(this).remove();
        if (cList.find('li').length == 0) {
            $('#controls').fadeOut();
            $('.nothing-message').show('fast');
        }
    });

    // Clear all courses button
    $('button#clear-all-courses').on('click', function () {
        cList.find('li').remove();
        $('#controls').fadeOut();
        $('.nothing-message').show('fast');
    });

    breadcrumb.on('click', '#next', function () {
        if (loadPlan(curr + 1) !== false) {
            $('.load-plan[data-item-id="' + curr + '"]').parent().removeClass('active');
            curr += 1;
            $('.load-plan[data-item-id="' + curr + '"]').parent().addClass('active');
        }

    });

    breadcrumb.on('click', '#prev', function () {
        if (loadPlan(curr - 1) !== false) {
            $('.load-plan[data-item-id="' + curr + '"]').parent().removeClass('active');
            curr -= 1;
            $('.load-plan[data-item-id="' + curr + '"]').parent().addClass('active');
        }
    });

    breadcrumb.on('click', '.load-plan', function () {
        if (loadPlan($(this).attr('data-item-id')) !== false) {
            $('.load-plan[data-item-id="' + curr + '"]').parent().removeClass('active');
            curr = $(this).attr('data-item-id');
            $(this).parent().addClass("active");
        }
    });


    // re-render the homepage with new language
    //$(document).on('click', '.lang', function () {
    //    $.get("/BounCoursePlanner/", function (data) {
    //        var regExp = /\<body>([\s\S]*)<\/body>/ig;
    //        $("body").html(regExp.exec(data)[1]);
    //    });
    //});

});

function clearSchedule() {
    var slots = $('.schedule-rows .time-slot');
    if (slots == undefined || slots.length == 0) return;
    slots.each(function (index) {
        $(this).html("");
        if ($(this).hasClass("contains-course"))
            $(this).removeClass("contains-course");
    });
}

function putCourse(name, hour) {
    var dh = /^([^0-9]*)([0-9]*)$/ig.exec(hour);
    var intD = Object.getKeyByValue(intToDay, dh[1]);
    var h = dh[2].length === 1 ? "0" + dh[2] : dh[2];
    var slot = $(".schedule-rows").find('.time-slot[data-day="' + intD + '"][data-time="' + h + ':00"]');
    if (!slot.hasClass("contains-course"))
        slot.addClass("contains-course");
    slot.html(slot.html() + '<p class="text-center">' + name + '</p>');
}

function loadPlan(i) {
    if (i < 0 || i >= Object.size(plans)) return false;
    clearSchedule();
    for (var j = 0; j < Object.size(plans[i]); j++) {
        var cName = plans[i][j]["course-name"];
        var cHours = plans[i][j]["course-hours"];
        var section = Object.keys(cHours)[0];
        for (var hour in cHours[section])
            if (cHours[section].hasOwnProperty(hour))
                putCourse(cName + "." + section, cHours[section][hour]);
    }
}
function process(funcName, data) {
    var ret;
    $.ajax({
        type: "POST",
        url: "http://localhost/BounCoursePlanner/AjaxHandler.php",
        async: false,
        data: {
            function: funcName,
            data: data
        },
        dataType: "json",
        success: function (returnData) {
            ret = returnData;
        }
    });
    return ret;
}