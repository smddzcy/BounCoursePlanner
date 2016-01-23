(function ($) {
    'use strict';

    var DayScheduleSelector = function (el, options) {
        this.$el = $(el);
        this.options = $.extend({}, DayScheduleSelector.DEFAULTS, options);
        this.render();
        this.attachEvents();
        this.$selectingStart = null;
    };

    DayScheduleSelector.DEFAULTS = {
        days: [0, 1, 2, 3, 4, 5, 6],
        startTime: '09:00',
        endTime: '21:01',
        interval: 60,
        stringDays: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        template: '<div class="day-schedule-selector">' +
        '<table class="schedule-table">' +
        '<thead class="schedule-header"></thead>' +
        '<tbody class="schedule-rows"></tbody>' +
        '</table>' +
        '<div>'
    };

    DayScheduleSelector.prototype.render = function () {
        this.$el.html(this.options.template);
        this.renderHeader();
        this.renderRows();
    };

    DayScheduleSelector.prototype.renderHeader = function () {
        var stringDays = this.options.stringDays
            , days = this.options.days
            , html = '';

        $.each(days, function (i, _) {
            html += '<th>' + (stringDays[i] || '') + '</th>';
        });
        this.$el.find('.schedule-header').html('<tr><th></th>' + html + '</tr>');
    };

    DayScheduleSelector.prototype.renderRows = function () {
        var start = this.options.startTime
            , end = this.options.endTime
            , interval = this.options.interval
            , days = this.options.days
            , $el = this.$el.find('.schedule-rows');

        $.each(generateDates(start, end, interval), function (i, d) {
            var daysInARow = $.map(new Array(days.length), function (_, i) {
                return '<td class="time-slot" data-time="' + hhmm(d) + '" data-day="' + days[i] + '"></td>'
            }).join();

            $el.append('<tr><td class="time-label">' + hmmAmPm(d) + '</td>' + daysInARow + '</tr>');
        });
    };

    DayScheduleSelector.prototype.isSelecting = function () {
        return !!this.$selectingStart;
    };

    DayScheduleSelector.prototype.select = function ($slot) {
        $slot.attr('data-selected', 'selected');
    };

    DayScheduleSelector.prototype.deselect = function ($slot) {
        $slot.removeAttr('data-selected');
    };

    function isSlotSelected($slot) {
        return $slot.is('[data-selected]');
    }

    function isSlotSelecting($slot) {
        return $slot.is('[data-selecting]');
    }

    function getSelection(plugin, $a, $b) {
        var $slots, small, large, temp;
        if (!$a.hasClass('time-slot') || !$b.hasClass('time-slot') ||
            ($a.data('day') != $b.data('day'))) {
            return [];
        }
        $slots = plugin.$el.find('.time-slot[data-day="' + $a.data('day') + '"]');
        small = $slots.index($a);
        large = $slots.index($b);
        if (small > large) {
            temp = small;
            small = large;
            large = temp;
        }
        return $slots.slice(small, large + 1);
    }

    DayScheduleSelector.prototype.attachEvents = function () {
        var plugin = this
            , options = this.options
            , $slots;

        this.$el.on('click', '.time-slot', function () {
            var day = $(this).data('day');
            if (!plugin.isSelecting()) {  // if we are not in selecting mode
                if (isSlotSelected($(this))) {
                    plugin.deselect($(this));
                }
                else {  // then start selecting
                    plugin.$selectingStart = $(this);
                    $(this).attr('data-selecting', 'selecting');
                    plugin.$el.find('.time-slot').attr('data-disabled', 'disabled');
                    plugin.$el.find('.time-slot[data-day="' + day + '"]').removeAttr('data-disabled');
                }
            } else {  // if we are in selecting mode
                if (day == plugin.$selectingStart.data('day')) {  // if clicking on the same day column
                    // then end of selection
                    plugin.$el.find('.time-slot[data-day="' + day + '"]').filter('[data-selecting]')
                        .attr('data-selected', 'selected').removeAttr('data-selecting');
                    plugin.$el.find('.time-slot').removeAttr('data-disabled');
                    plugin.$el.trigger('selected.artsy.dayScheduleSelector', [getSelection(plugin, plugin.$selectingStart, $(this))]);
                    plugin.$selectingStart = null;
                }
            }
        });

        this.$el.on('mouseover', '.time-slot', function () {
            var $slots, day, start, end, temp;
            if (plugin.isSelecting()) {  // if we are in selecting mode
                day = plugin.$selectingStart.data('day');
                $slots = plugin.$el.find('.time-slot[data-day="' + day + '"]');
                $slots.filter('[data-selecting]').removeAttr('data-selecting');
                start = $slots.index(plugin.$selectingStart);
                end = $slots.index(this);
                if (end < 0) return;  // not hovering on the same column
                if (start > end) {
                    temp = start;
                    start = end;
                    end = temp;
                }
                $slots.slice(start, end + 1).attr('data-selecting', 'selecting');
            }
        });
    };

    DayScheduleSelector.prototype.serialize = function () {
        var plugin = this
            , selections = {};

        $.each(this.options.days, function (_, v) {
            var start, end;
            start = end = false;
            selections[v] = [];
            plugin.$el.find(".time-slot[data-day='" + v + "']").each(function () {
                // Start of selection
                if (isSlotSelected($(this)) && !start) {
                    start = $(this).data('time');
                }

                // End of selection (I am not selected, so select until my previous one.)
                if (!isSlotSelected($(this)) && !!start) {
                    end = $(this).data('time');
                }

                // End of selection (I am the last one :) .)
                if (isSlotSelected($(this)) && !!start && $(this).is(".time-slot[data-day='" + v + "']:last")) {
                    end = secondsSinceMidnightToHhmm(
                        hhmmToSecondsSinceMidnight($(this).data('time')) + plugin.options.interval * 60);
                }

                if (!!end) {
                    selections[v].push([start, end]);
                    start = end = false;
                }
            });
        });
        return selections;
    };

    DayScheduleSelector.prototype.deserialize = function (schedule) {
        var plugin = this, i;
        $.each(schedule, function (d, ds) {
            var $slots = plugin.$el.find('.time-slot[data-day="' + d + '"]');
            $.each(ds, function (_, s) {
                for (i = 0; i < $slots.length; i++) {
                    if ($slots.eq(i).data('time') >= s[1]) {
                        break;
                    }
                    if ($slots.eq(i).data('time') >= s[0]) {
                        plugin.select($slots.eq(i));
                    }
                }
            })
        });
    };

    function Plugin(option) {
        return this.each(function () {
            var $this = $(this)
                , data = $this.data('artsy.dayScheduleSelector')
                , options = typeof option == 'object' && option;

            if (!data) {
                $this.data('artsy.dayScheduleSelector', (data = new DayScheduleSelector(this, options)));
            }
        })
    }

    $.fn.dayScheduleSelector = Plugin;

    function generateDates(start, end, interval) {
        var numOfRows = Math.ceil(timeDiff(start, end) / interval);
        return $.map(new Array(numOfRows), function (_, i) {
            // need a dummy date to utilize the Date object
            return new Date(new Date(2000, 0, 1, start.split(':')[0], start.split(':')[1]).getTime() + i * interval * 60000);
        });
    }

    function timeDiff(start, end) {   // time in HH:mm format
        // need a dummy date to utilize the Date object
        return (new Date(2000, 0, 1, end.split(':')[0], end.split(':')[1]).getTime() -
            new Date(2000, 0, 1, start.split(':')[0], start.split(':')[1]).getTime()) / 60000;
    }

    function hmmAmPm(date) {
        var hours = date.getHours()
            , minutes = date.getMinutes()
            , ampm = hours >= 12 ? 'pm' : 'am';
        return hours + ':' + ('0' + minutes).slice(-2) + ampm;
    }

    function hhmm(date) {
        var hours = date.getHours()
            , minutes = date.getMinutes();
        return ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2);
    }

    function hhmmToSecondsSinceMidnight(hhmm) {
        var h = hhmm.split(':')[0]
            , m = hhmm.split(':')[1];
        return parseInt(h, 10) * 60 * 60 + parseInt(m, 10) * 60;
    }

    function secondsSinceMidnightToHhmm(seconds) {
        var minutes = Math.floor(seconds / 60);
        return ('0' + Math.floor(minutes / 60)).slice(-2) + ':' +
            ('0' + (minutes % 60)).slice(-2);
    }

    window.DayScheduleSelector = {
        ssmToHhmm: secondsSinceMidnightToHhmm,
        hhmmToSsm: hhmmToSecondsSinceMidnight
    };

})(jQuery);