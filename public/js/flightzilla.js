/*global jQuery */
(function($) { $(function() {
    var f = {
        bugs: {},
        write: {},
        toggleBugs: {},
        toggleChart: {},
        bugTable: {},
        charts: {},
        headerRows: {},
        searchable: {},
        modalDiv: null,
        searchTimeout: 0,
        init: function() {
            this.headerRows = $('.tableHeader td[colspan="6"], .campaignName');
            this.bugs = $('tr');
            this.write = $('#quickList');
            this.toggleBugs = $('.toggleBugs');
            this.toggleChart = $('.toggleChart');
            this.bugTable = $('.bugTable');
            this.charts = {};
            this.modalDiv = $('#flightzillaModal');
            this.searchable = $('.tableHeader td[colspan="6"], .campaignName, tr').find('span, td.bugDesc, a.bugLink');
        },
        hideBugs: function() {
            this.bugTable.each(function() {
                $(this).show().find('.tableHeader').show();
                if ($(this).find('tr:not(.tableHeader):visible').length === 0) {
                    $(this).hide();
                }
            });
        },
        modal: function(head, body) {
            this.modalDiv.find('.modal-header h3').html(head);
            this.modalDiv.find('.modal-body p').empty().append(body);
            this.modalDiv.modal('show');
        },
        sortHelper: function sortObj(aInput){
            var aTemp = [],
                aOutput = [],
                nIndex,
                sKey = null;
            for (sKey in aInput) {
                aTemp.push([sKey, aInput[sKey]]);
            }

            aTemp.sort(function () { return arguments[0][1] > arguments[1][1]; });
            for (nIndex = aTemp.length - 1; nIndex >= 0; nIndex--) {
                aOutput[aTemp[nIndex][0]] = aTemp[nIndex][1];
            }

            return aOutput;
        },
        quickList: function() {
            var buffer = '';
            this.write.hide().empty();
            $('input:checkbox:checked').each(function() {
                buffer += $(this).parents('tr').find('td:eq(1) > a').text() + ',';
            });

            if (buffer !== '') {
                this.write.show().html('List:&nbsp;' + buffer.replace(/,$/,''));
            }
        },
        delay: (function() {
            var searchTimeout = this.searchTimeout;
            return function(callback, ms) {
              clearTimeout (searchTimeout);
              searchTimeout = setTimeout(callback, ms);
            };
        })()
    };

    f.init();
    f.bugTable.on('click', 'a.allBugs, a.noBugs', function() {
        var v = $(this).hasClass('allBugs') ? 'checked' : false;
        $(this).parents('table').find(':checkbox:visible').prop('checked', v);
        f.quickList();
    });

    f.toggleBugs.on('click', function() {
        f.toggleBugs.parents('.nav-list').find('li').removeClass('active');
        var v = $('.' + $(this).parent('li').addClass('active').end().data('target')).parents('tr');
        if (v.length) {
            f.bugs.hide();
            v.show();
        }
        else {
            f.bugs.show();
        }

        f.hideBugs();
    });

    f.toggleChart.on('click', function() {
        var $this = $(this),
            li = $this.parent('li'),
            target = $this.data('target'),
            container = $('#' + $this.data('container')),
            tab, tabContent;

        tab = (!$this.data('tab')) ? $this.parents('.tabContainer') : $('#' + $this.data('tab'));
        tabContent = tab.find('.tab-content');

        if (li.hasClass('active')) {
            li.removeClass('active');
            tab.addClass('hidden');
            if (f.charts[target]) {
                f.charts[target].destroy();
            }
        }
        else {
            if (!$this.data('toggle')) {
                li.addClass('active');
                if (!$this.data('noajax')) {
                    tab.removeClass('hidden');
                    $this.append($('<span class="pl10">Loading ...</span>'));
                }
            }
            else {
                tabContent.find('.chartContainer').removeClass('active');
                container.addClass('active');
            }

            if ($this.data('mode')) {
                $('#loading').clone().show().appendTo(tabContent);
                var dataType = ($this.data('mode') === 'campaign') ? 'html' : 'script';
                $.ajax({
                    type: 'POST',
                    url: BASE_URL + '/flightzilla/analytics/data',
                    dataType: dataType,
                    data: {
                        portal: target,
                        container: $this.data('container'),
                        mode: $this.data('mode'),
                        "which": $this.data('which')
                    }
                }).done(function(msg) {
                    if ($this.data('mode') === 'conversion') {
                        f.charts[target] = new Highcharts.Chart(options);
                    }
                    else {
                        container.html(msg);
                    }
                }).fail(function(jqXHR, textStatus) {
                    alert( "Request failed: " + textStatus );
                }).always(function() {
                    tab.find('.loading').remove();
                    $this.find('span').remove();
                    f.init();
                });
            }
        }
    });

    /**
     * The search-bar functionality
     */
    $('#searchId').on('keyup', function() {
        var $this = $(this);
        f.delay(function() {
            var searchText = $.trim($this.val()),
                result, t;

            if (searchText !== '') {
                result = f.searchable.filter(function() {
                    t = (new RegExp(searchText, "ig")).exec($(this).text());
                    return (t && t.length);
                });

                if (result.length) {
                    f.bugs.hide();
                    result.each(function() {
                        var $that = $(this),
                            tables = $that.parents('.bugTable');

                        tables.show();
                        if ($that.hasClass('caption') === true) {
                            tables.find('tr').show();
                        }
                        else{
                            $that.parents('tr').show();
                        }
                    });
                }
                else {
                    f.bugs.show();
                }
            }
            else {
                f.bugs.show();
            }

            f.hideBugs();
            window.scrollTo(0,0);
        }, 500);
    });

    /**
     * Retrieve the merge-list for the selected tickets
     */
    f.bugTable.on('click', 'a.mergelist', function() {
        var bugs = f.write.text();

        f.modal('Merge-List', $('#loading').clone().css({top:0}).show());
        $.ajax({
            type: 'POST',
            url: BASE_URL + '/flightzilla/mergy/mergelist',
            data: {
                tickets: bugs
            }
        }).done(function(msg) {
            f.modal('Merge-List', msg);
        }).fail(function(jqXHR, textStatus) {
            alert( "Request failed: " + textStatus );
        });
    });

    /**
     * Toggle the detailed merge-results-content
     */
    $('#container').on('click', 'button.toggle', function() {
        $(this).parents('.merge-result').find('pre').toggleClass('hidden');
    });

    /**
     * Perform the merge action
     */
    $('.merge-button').on('click', function() {
        var bugs = f.write.text(),
            data = {
                tickets: bugs
            },
            container = $('#container').empty(),
            aRepos = container.data('repos'),
            i, len;

        if ($(this).hasClass('commit')) {
            data.commit = true;
        }

        if (bugs.length) {
            for (i=0,len=aRepos.length; i<len; i++) {
                data.repo = aRepos[i];
                (function(index) {
                    container.append($('#loading').clone().removeAttr('id').css({top:0}).addClass('load-' + aRepos[index]).show());
                    $.ajax({
                        type: 'POST',
                        url: BASE_URL + '/flightzilla/mergy/merge',
                        data: data
                    }).done(function(msg) {
                        container.append(msg);
                    }).fail(function(jqXHR, textStatus) {
                        alert( "Request failed: " + textStatus );
                    }).always(function() {
                        container.find('.load-' + aRepos[index]).remove();
                    });
                }(i));
            }
        }
    });

    /**
     * Create the content for the release-log
     */
    f.bugTable.on('click', 'a.changelist', function() {
        var head = "|| [" + BUGZILLA + "/buglist.cgi?quicksearch=##LIST## Liste] || ||||  '''Titel'''  || '''Assignee''' ||",
            bugs = [],
            string = "",
            $this, name, time, bug,
            counter = {};
        $('input:checkbox:checked').each(function() {
            $this = $(this).parents('.bug');
            name = $this.data('assignee');
            time =  parseFloat($this.find('.time.green').eq(0).data('time'));
            bug = $this.find('.bugLink');

            bugs.push(bug.text());
            string += '|| [' + bug.attr('href') + ' Bug #' + bug.text() + ']|| ' + $.trim($this.find('.bugProd').text()) + ' |||| ' + $this.find('.bugDesc').text() + ' || ' + $this.data('assignee') + ' ||\n';
            if (!counter[name]) {
                counter[name] = 0;
            }

            if (time) {
                counter[name] += time;
            }
        });

        counter = f.sortHelper(counter);
        for (name in counter) {
            break;
        }

        string += "|| '''Champion''' ||||||||  [[Image(pokal_icons.png)]]  '''" + name + "''' [[Image(pokal_icons.png)]]  ||";
        f.modal('Release-Log', '<textarea class="input-xxxlarge">' + head.replace(/##LIST##/, bugs.join(',')) + '\n' + string + '</textarea>');
    });

    /**
     * Create the content for the release-mail
     */
    f.bugTable.on('click', 'a.changemail', function() {
        var bugs = {},
            string = "",
            $this, type, component, bug;
        $('input:checkbox:checked').each(function() {
            $this = $(this).parents('.bug');
            type = $this.data('type');
            component = $this.data('component');
            bug = $this.find('.bugLink');

            if (typeof bugs[type] === 'undefined') {
                bugs[type] = [];
            }

            if (typeof bugs[type][component] === 'undefined') {
                bugs[type][component] = [];
            }

            bugs[type][component].push({
                nr: bug.text(),
                text: $this.find('.bugDesc').data('release') + ' (' + $.trim($this.find('.bugProd').text()) + ')'
            });
        });

        for (type in bugs) {
            string += "\n" + type + "\n";
            for (component in bugs[type]) {
                string += component + "\n";
                for (bug in bugs[type][component]) {
                    string += '#' + bugs[type][component][bug].nr + ': ' + bugs[type][component][bug].text + "\n";
                }
            }
        }

        f.modal('Release-Log', '<textarea class="input-xxxlarge">' + string + '</textarea>');
    });

    /**
     * Show the details of a ticket
     */
    f.bugTable.on('click', 'a.ticket-detail', function() {
        var iTicket = $(this).data('ticket');

        f.modal('Loading ticket #' + iTicket, $('#loading').clone().removeAttr('id').css({top:0}).show());
        $.ajax({
            type: 'POST',
            url: BASE_URL + '/flightzilla/index/detail',
            data: {
                ticket: iTicket
            }
        }).done(function(msg) {
            f.modal('Details for Ticket #' + iTicket, msg);
        }).fail(function(jqXHR, textStatus) {
            alert( "Request failed: " + textStatus);
        });
    });

    /**
     * Get the list of tickets for the modify-view
     */
    f.bugTable.on('click', 'a.modify-tickets', function() {
        var bugs = f.write.text(),
            data = {
                tickets: bugs
            };

        f.modal('Loading tickets', $('#loading').clone().removeAttr('id').css({top:0}).show());
        $.ajax({
            type: 'POST',
            url: BASE_URL + '/flightzilla/ticket/list',
            data: data
        }).done(function(msg) {
            f.modal('Modify Tickets', msg);
        }).fail(function(jqXHR, textStatus) {
            alert( "Request failed: " + textStatus);
        });
    });

    /**
     * Execeute the modify-submit
     */
    $(document).on('submit', '#change-form', function(e) {
        var $this = $(this);
        f.modal('Modifying tickets', $('#loading').clone().removeAttr('id').css({top:0}).show());

        $.ajax({
            type: 'POST',
            url: BASE_URL + '/flightzilla/ticket/modify',
            data: $this.serializeArray()
        }).done(function(msg) {
            f.modal('Modified Tickets', msg);
        }).fail(function(jqXHR, textStatus) {
            alert( "Request failed: " + textStatus);
        });

        e.preventDefault();
    });

    $('.largeGray').click(function() {
        $(this).parents('.memberBox').find('.allTickets').toggleClass('hidden');
    });

    $('.bugzilla-link').click(function() {
        $('#buglist-form').submit();
    });

    $('.toggle').on('click', function() {
        $('.' + $(this).data('target')).toggle();
    });

    $('.toggleNav').on('click', function() {
        $(this).toggleClass('btn-primary').parents('.nav-list').find('a.toggleChart').trigger('click');
    });

    $('input:checkbox').on('click', function() {
        f.quickList();
    });

    $('div.description a, span.theme a').tooltip();

    $('.tablesorter').tablesorter();
}); }(jQuery));
