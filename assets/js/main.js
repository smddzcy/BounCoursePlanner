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
$(document).ready(function() {
  /**
   * Appends an indicator to an element
   * @param  {String,Node} el      Element to append. ex.: "#hereXx"
   * @param  {int} size            Size of the indicator
   * @param  {String} wrapper      Wrapper element
   * @param  {String} wrapperStyle Wrapper element style
   */
  var appendIndicator = function(el, size, wrapper, wrapperStyle) {
    size = parseInt(size);
    if (size < 1) size = 1;
    else if (size > 3) size = 3;

    var toAppend = '<i class="fa fa-cog fa-spin fa-' + size +
      'x fa-fw indicator"></i><span class="sr-only">Loading...</span>';
    if (typeof wrapper !== 'undefined')  {
      if (typeof wrapperStyle === 'undefined') wrapperStyle = "";
      toAppend = "<" + wrapper + " style=\"" + wrapperStyle + "\">" + toAppend + "</" + wrapper + ">";
      $(el).append(toAppend);
    }
  };

  /**
   * Removes an indicator from an element
   * @param  {String,Node} el Element to remove the indicator from. ex.: "#hereXx"
   */
  var removeIndicator = function(el, hasWrapper) {
    el = $(el).find('.indicator')[0];
    if (el) {
      if (hasWrapper) el = el.parentNode;
      el.parentNode.removeChild(el);
    }
  };


  var cInsert = $("#course-insert");
  var cList = $('#course-list');
  var pagination = $('.pagination');
  var breadcrumb = $('#breadcrumb');
  var semesters = $("#semesters");
  var possibleSchedulesEl = $('#possible-schedules');
  var conflictedHoursEl = $('#conflicted-hours');
  var totalCreditEl = $('#total-credit');
  // popup for share buttons
  $('a.popup').on('click', function() {
    var newwindow = window.open($(this).attr('href'), '', 'height=400,width=600');
    if (window.focus) {
      newwindow.focus();
    }
    return false;
  });

  $('#show-duplicates').checkboxpicker({
    html: true,
    offLabel: '<span class="glyphicon glyphicon-remove">',
    onLabel: '<span class="glyphicon glyphicon-ok">'
  });

  var year, semester;
  // fill semester picker
  appendIndicator("#semesters", 2, "li", "text-align: center;width: 100%;margin:10px auto;");

  processReq("getSemesters", 1, true, function(semesterData) {
    removeIndicator("#semesters", true);
    if (semesterData.length) {
      for (var i in semesterData) {
        if (semesterData.hasOwnProperty(i) && semesterData[i].length) {
          year = parseInt(semesterData[i][0]);
          semester = parseInt(semesterData[i][1]);
          semesters.append(
            '<li><a href="#" class="chooseSemester" data-year="' + year + '" data-semester="' + semester +
            '">' +
            year + '/' + (year + 1) + '-' + semester + '</a></li>'
          );
        }
      }
    }
  });

  String.prototype.turkishToUpper = function() {
    var string = this;
    var letters = {
      "i": "I",
      "ş": "S",
      "ğ": "G",
      "ü": "U",
      "ö": "O",
      "ç": "C",
      "ı": "I"
    };
    string = string.replace(/[iışğüçö]+/g, function(letter) {
      return letters[letter];
    });
    return string.toUpperCase();
  };

  Object.getKeyByValue = function(obj, value) {
    for (var key in obj) {
      if (obj.hasOwnProperty(key)) {
        if (obj[key] == value) return key;
      }
    }
  };

  Object.size = function(obj) {
    var size = 0,
      key;
    for (key in obj) {
      if (obj.hasOwnProperty(key)) size++;
    }
    return size;
  };

  // auto-completion for course names
  processReq("getAvailableCourses", 1, true, function(availableTags) {
    var availableTagsWithSections = availableTags[1];
    var currWithSections = false;
    availableTags = availableTags[0];

    cInsert.autocomplete({
      source: function(request, response) {
        var results = $.ui.autocomplete.filter(availableTags, request.term);
        response(results.slice(0, 10));
      }
    }).keyup(function(e) {
      if (e.which === 13) {
        $(".ui-menu-item").hide();
      }
      cInsert.val(cInsert.val().turkishToUpper());
    }).keydown(function(event) {
      if (event.keyCode == 13) {
        $("#add-course").click();
      } else if (event.keyCode == 190 && currWithSections === false) {
        cInsert.autocomplete({
          source: function(request, response) {
            var results = $.ui.autocomplete.filter(availableTagsWithSections, request.term);
            response(results.slice(0, 10));
          }
        });
        currWithSections = true;
      } else if (cInsert.val().indexOf(".") == -1 && currWithSections === true) {
        cInsert.autocomplete({
          source: function(request, response) {
            var results = $.ui.autocomplete.filter(availableTags, request.term);
            response(results.slice(0, 10));
          }
        });
        currWithSections = false;
      }
    });

    var semesterText = $("#semesterText");

    $('.dropdown-menu').on('click', '.chooseSemester', function() {
      $('button#clear-all-courses').click();

      $('.chooseSemester.active').removeClass('active');
      $(this).addClass('active');

      semesterText.html('<b>S: ' + $(this).html() + '</b>');

      processReq("getAvailableCourses", {
        "year": $(this).data('year'),
        "semester": $(this).data('semester')
      }, true, function(newTags) {
        availableTags = newTags[0];
        availableTagsWithSections = newTags[1];
      });
    });

    var addCourseWithName = function(courseName, alertOnError = true){
      if (courseName.length === 0) {
        if(alertOnError) alert(COURSE_EMPTY);
        return;
      } else if (availableTags.indexOf(courseName) == -1 && availableTagsWithSections.indexOf(courseName) == -1) {
        if(alertOnError) alert(COURSE_NOT_VALID);
        return;
      } else if ($('#course-list:contains("' + (courseName.indexOf(".") !== -1 ? courseName.split(".")[0] : courseName) +
          '")').length !== 0) {
        if(alertOnError) alert(COURSE_ALREADY_ADDED);
        return;
      }

      if ('.nothing-message') {
        $('.nothing-message').hide('slide', {
          direction: 'left'
        }, 300);
      }

      var newCourse = '<li>' + '<p>' + courseName + '</p>' + '</li>';
      $('#course-list').append(newCourse);
      $('input').val('');
      $('#controls').fadeIn();

      // Persist course in local storage
      var cachedCourses = JSON.parse(localStorage.courses || '[]');
      if (cachedCourses.indexOf(courseName) === -1) {
        cachedCourses.push(courseName);
        localStorage.courses = JSON.stringify(cachedCourses);
      }
    }

    // Add cached courses if we have some
    var cachedCourses = JSON.parse(localStorage.courses || '[]');
    for (var i in cachedCourses) {
      addCourseWithName(cachedCourses[i], false);
    }

    var removedPanelText = false;
    //  add course button click function
    $('button#add-course').on('click', function() {
      var courseName = $('input[name=course-insert]').val().turkishToUpper();

      // Remove the panel text, only once.
      if (!removedPanelText) {
        var panelText = $('#panel-text');
        if (panelText.length > 0) {
          // Fade out the panel text, then remove it after a second
          panelText.fadeOut();
          setTimeout(function(){
            panelText[0].parentNode.removeChild(panelText[0]);
          }, 1000);

          $('#main-panel').addClass('focus');
          removedPanelText = true;
        }
      }

      addCourseWithName(courseName);
    });

    // load the plan
    $('button#find-best-plan').on('click', function() {
      var courseList = [];
      var freeHours = [];
      cList.find('li:not(.course-disabled)').find('p').each(function(index, el) {
        courseList.push(el.innerHTML);
      });
      if (courseList.length === 0) return;

      $('.schedule-rows').find('.time-slot').each(function(index, el) {
        if (el.getAttribute('data-selected') == 'selected')
          freeHours.push(intToDay[el.getAttribute('data-day')] + el.getAttribute('data-time').split(":")[
            0].replace(
            /^0/, ''));
      });

      var chosenSemester = $('.chooseSemester.active');

      callbackFn = function(result) {
        if (Object.size(result) === 0) {
          alert("Sorry, we couldn't find any course plan.");
        } else if (result.hasOwnProperty("error")) {
          alert(result.error);
        } else {
          curr = 0;
          var conflict = result.conflict;
          plans = result.plans;
          var plansSize = Object.size(plans);

          pagination.html('');
          pagination.append('<li><a id="prev">«</a></li>');
          for (var i = 1; i <= plansSize; i++) {
            pagination.append('<li><a class="load-plan" data-item-id="' + (i - 1) + '">' + i +
              '</a></li>');
          }
          pagination.append('<li><a id="next">»</a></li>');

          $('.load-plan[data-item-id="' + curr + '"]').parent().addClass('active');
          if (breadcrumb.hasClass('hidden'))
            breadcrumb.removeClass('hidden');

          loadPlan(curr);
          possibleSchedulesEl.html(plansSize);
          conflictedHoursEl.html(conflict);
        }
      }

      if (chosenSemester.length > 0) {
        processReq("findBestPlan", {
          "year": chosenSemester.data('year'),
          "semester": chosenSemester.data('semester'),
          "courseList": courseList,
          "freeHours": freeHours,
          "showDuplicates": $("#show-duplicates").prop('checked')
        }, true, callbackFn);
      } else {
        processReq("findBestPlan", {
          "courseList": courseList,
          "freeHours": freeHours,
          "showDuplicates": $("#show-duplicates").prop('checked')
        }, true, callbackFn);
      }
    });

    cList.on('click', 'li', function() {
      $(this).toggleClass('course-disabled');
    });

    // double click to remove
    cList.on('dblclick', 'li', function() {
      $(this).remove();
      if (cList.find('li').length === 0) {
        $('#controls').fadeOut();
        $('.nothing-message').show('fast');
      }

      // Remove course from local storage
      var cachedCourses = JSON.parse(localStorage.courses || '[]');
      var index = cachedCourses.indexOf($(this).html());
      cachedCourses.splice(index, 1);
      localStorage.courses = JSON.stringify(cachedCourses);
    });

    // Clear all courses button
    $('button#clear-all-courses').on('click', function() {
      cList.find('li').remove();
      $('#controls').fadeOut();
      $('.nothing-message').show('fast');

      // Clear local storage
      localStorage.courses = '[]';
    });

    breadcrumb.on('click', '#next', function() {
      if (loadPlan(curr + 1) !== false) {
        $('.load-plan[data-item-id="' + curr + '"]').parent().removeClass('active');
        curr += 1;
        $('.load-plan[data-item-id="' + curr + '"]').parent().addClass('active');
      }

    });

    breadcrumb.on('click', '#prev', function() {
      if (loadPlan(curr - 1) !== false) {
        $('.load-plan[data-item-id="' + curr + '"]').parent().removeClass('active');
        curr -= 1;
        $('.load-plan[data-item-id="' + curr + '"]').parent().addClass('active');
      }
    });

    breadcrumb.on('click', '.load-plan', function() {
      if (loadPlan($(this).attr('data-item-id')) !== false) {
        $('.load-plan[data-item-id="' + curr + '"]').parent().removeClass('active');
        curr = parseInt($(this).attr('data-item-id'));
        $(this).parent().addClass("active");
      }
    });

  });

  // re-render the homepage with new language
  //$(document).on('click', '.lang', function () {
  //    $.get("/BounCoursePlanner/", function (data) {
  //        var regExp = /\<body>([\s\S]*)<\/body>/ig;
  //        $("body").html(regExp.exec(data)[1]);
  //    });
  //});

  var clearSchedule = function() {
    var slots = $('.schedule-rows .time-slot');
    if (slots === undefined || slots.length === 0) return;
    slots.each(function(index) {
      $(this).html("");
      if ($(this).hasClass("contains-course"))
        $(this).removeClass("contains-course");
    });
  }

  var putCourse = function(name, hour) {
    var dh = /^([^0-9]*)([0-9]*)$/ig.exec(hour);
    var intD = Object.getKeyByValue(intToDay, dh[1]);
    var h = dh[2].length === 1 ? "0" + dh[2] : dh[2];
    var slot = $(".schedule-rows").find('.time-slot[data-day="' + intD + '"][data-time="' + h + ':00"]');
    if (!slot.hasClass("contains-course"))
      slot.addClass("contains-course");
    slot.html(slot.html() + '<p class="text-center">' + name + '</p>');
  }

  var loadPlan = function(i) {
    if (i < 0 || i >= Object.size(plans)) return false;
    clearSchedule();

    var totalCredit = plans[i]
      .map(function(course) { return parseInt(course["course-credit"]) || NaN; })
      .reduce(function(acc, el) { return acc + el; }, 0);
    totalCreditEl.html(totalCredit);

    for (var j = 0; j < Object.size(plans[i]); j++) {
      var cName = plans[i][j]["course-name"];
      var cHours = plans[i][j]["course-hours"];
      var cCredit = plans[i]
      var section = Object.keys(cHours)[0];
      for (var hour in cHours[section])
        if (cHours[section].hasOwnProperty(hour))
          putCourse(cName + "." + section, cHours[section][hour]);
    }
  }

});

var processReq = function(funcName, data, isAsync, fn) {
  if (typeof isAsync === 'undefined' || isAsync === null) {
    isAsync = false;
  }

  var ret;
  $.ajax({
    type: "POST",
    url: "http://localhost/BounCoursePlanner/AjaxHandler.php",
    async: isAsync,
    timeout: 60000, // 60 secs timeout
    data: {
      function: funcName,
      data: data
    },
    dataType: "json",
    success: function(returnData) {
      ret = returnData;
      if (isAsync === true) {
        fn(returnData);
      }
    },
    error: function(xhr, status, errorThrown) {
      console.log("error: ");
      console.log(xhr);
      ret = xhr;
      if (isAsync === true) {
        fn(xhr);
      }
    }
  });
  return ret;
}
