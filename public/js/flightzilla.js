/*global jQuery, BASE_URL */
(function($) { $(function() {
    window.f = {
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
        wrapper: null,
        dContent: null,
        loader: null,
        dStatus: null,
        aSemaphore: null,
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
            this.wrapper = $('div.wrapper');
            this.dContent = $('#dContent');
            this.dStatus = $('#dStatus');
            this.loader = $('#loading');
            this.aSemaphore = {};

            this.dragging();
            this.functionStack();
            this.tooltips();
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
        })(),

        /**
         * Init draggables
         */
        dragging: function() {
            if ($('div.login').length === 0) {
                $.get(BASE_URL + '/flightzilla/team/members', function(msg) {
                    if ($('#ticket-dropper').length === 0) {
                        $('<div />', {
                            id: 'ticket-dropper',
                            style: 'display:none'
                        }).html(msg).appendTo('body');
                    }

                    $('.draggable').draggable({
                        revert: true,
                        zIndex: 1000,
                        start: function(event, ui) {
                            var dropper = $('#ticket-dropper');
                            if (dropper.length) {
                                dropper.show().position({
                                    of: ui.helper.parents('.row, tr').eq(0),
                                    my: "center top",
                                    at: "center bottom+10",
                                    collision: "flipfit"
                                });
                            }
                        },
                        stop: function(event, ui) {
                            $('#ticket-dropper').hide();
                        }
                    });

                    f.dropping();
                });
            }
        },

        /**
         * Init droppables
         */
        dropping: function() {
            $('.droppable').droppable({
                activeClass: "btn-success",
                tolerance: "pointer",
                drop: function( event, ui ) {
                    var data = {
                        tickets: ui.draggable.data('ticket'),
                        drop: $(this).data('drop'),
                        week: $(this).data('week'),
                        user: $(this).data('user')
                    };

                    f.modal('Loading tickets', $('#loading').clone().removeAttr('id').css({top:0}).show());
                    $.ajax({
                        type: 'POST',
                        url: BASE_URL + '/flightzilla/ticket/list',
                        data: data
                    }).done(function(msg) {
                        f.modal('Modify Tickets', msg);
                        f.bindTicketModify();
                    }).fail(function(jqXHR, textStatus) {
                        alert( "Request failed: " + textStatus);
                    });
                },
                over: function(event, ui) {
                    var $this = $(this);
                    $this.toggleClass('btn-success').addClass('btn-danger');
                    if ($this.hasClass('close-tickets') === true) {
                        $('#ticket-dropper').hide();
                    }
                },
                out: function(event, ui) {
                    $(this).toggleClass('btn-success').removeClass('btn-danger');
                }
            });
        },

        /**
         * Enable hover/popover
         */
        tooltips: function() {
            $('div.description a, span.theme a, a.tooltip, .tipper').tooltip();
            $('.j-popover').each(function() {
                var $this = $(this),
                    source = $('#' + $this.data('source')),
                    content = source.css({
                        width: source.width()
                    }).html();

                $this.click(function(e) {
                    e.preventDefault();
                    $this.find('.tipper').tooltip();
                });
                if (content && content.length) {
                    $this.popover({
                        html: true,
                        content: content,
                        container: 'body'
                    });
                }
                else {
                    $this.remove();
                }
            });
        },

        /**
         * Load & refresh the content of the status-bar
         */
        loadStatus: function() {
            var $dStatus = this.dStatus,
                aSema = this.aSemaphore;
            if ($dStatus.length && !aSema.status && !aSema.list) {
                aSema.status = true;
                $.get($dStatus.data('data'), function(s) {
                    $dStatus.html(s).show();
                    f.tooltips();
                    aSema.status = false;
                });
            }
        },

        /**
         * Refresh the wrapper-content
         */
        refresh: function() {
            var $dContent = this.dContent,
                $loader = this.loader,
                aSema = this.aSemaphore;

            /* Hide the content on reload */
            this.modalDiv.modal('hide');
            $loader.css({
                top:'20px'
            }).show();
            $dContent.hide();

            if ($dContent.length && $dContent.data('data') && !aSema.list) {
                aSema.list = true;

                $.get($dContent.data('data'), function(s) {
                    $dContent.html(s).show();

                    f.init();
                }).error(function() {
                    $dContent.html('<div class="alert alert-danger"><strong>The request-failed!</strong></div>').show();

                }).complete(function() {
                    $loader.hide();
                    aSema.list = false;
                });
            }
            else {
                window.location.reload();
            }
        },

        /**
         * Execute the modify-submit
         */
        bindTicketModify: function() {

            $('#change-form').off('submit').on('submit', function(e) {
                var $this = $(this);
                f.modal('Modifying tickets', $('#loading').clone().removeAttr('id').css({top:0}).show());

                $('blockquote', $this).each(function() {
                    var $that = $(this);
                    $('.ticket' + $that.data('ticket')).css({
                        opacity: 0.5
                    });
                });

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
        },

        /**
         * Some common bindings, which should be attached during initialization
         */
        functionStack: function() {
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
                        var dataType = ($this.data('mode') === 'conversion') ? 'script' : 'html';
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
                        result, t, v, r;

                    if (searchText !== '') {
                        result = f.searchable.filter(function() {
                            v = $(this);
                            r = new RegExp(searchText, "ig");
                            t = (r.exec(v.text()));
                            if (!(t && t.length)) {
                                t = r.exec(v.parents('tr.bug').data('assignee'));
                            }

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
                    bug = $this.find('.bugLink').eq(0);

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
                f.modal('Release-Log', '<textarea class="input-xxxlarge form-control">' + head.replace(/##LIST##/, bugs.join(',')) + '\n' + string + '</textarea>');
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
            f.wrapper.on('click', 'a.ticket-detail', function() {
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
                    f.bindTicketModify();
                }).fail(function(jqXHR, textStatus) {
                    alert( "Request failed: " + textStatus);
                });
            });

            /**
             * Open the print-view
             */
            f.bugTable.on('click', 'a.print-link', function() {
                $('#buglist-form').prop('target', '_blank').prop('action', PRINT).submit();
            });

            /**
             * Toggle ticket-tables after a h3
             */
            $('h3.table-toggle').click(function() {
                $('#buglist-form').toggle();
            });

            /**
             * Toggle tickets in team-dash view
             */
            $('.large-number-gray').click(function() {
                $(this).parents('.member-box').find('.allTickets').toggleClass('hidden');
            });

            /**
             * Toggle ticket-list in the team-sprint view
             */
            $('.member-name').click(function() {
                var $this = $(this);
                $this.parents('.member-box').next('table.bugTable').toggleClass('hidden');
            });

            /**
             * Toggle Project-Details/Commentsf
             */
            f.wrapper.on('click', 'a.detail-toggle', function() {
                $(this).parents('.member-box').find('.project-detail').toggleClass('hidden');
            });

            f.bugTable.on('click', 'a.bugzilla-link', function() {
                $('#buglist-form').prop('target', '_blank').prop('action', GO_BUGZILLA).submit();
            });

            /**
             * Toggle-helper
             *
             * Link gets class "toggle", target should have the class in triggers data-target
             */
            $('.toggle').on('click', function() {
                $('.' + $(this).data('target')).toggle();
            });

            $('.toggleNav').on('click', function() {
                $(this).toggleClass('btn-primary').parents('.nav-list').find('a.toggleChart').trigger('click');
            });

            $('.toggleNext').each(function() {
                var $this = $(this),
                    f = $this.data('toggleNext');
                $this.on('click', function() {
                    $this.next(f).toggle();
                });

                $this.next(f).toggle();
            });

            /**
             * Add all selected tickets to the quick-list
             */
            $('input:checkbox').on('click', function() {
                f.quickList();
            });

            /**
             * Init the table-sorter
             */
            $('.tablesorter').tablesorter({
                selectorHeaders: '.tableSort > td,th'
            });
        }
    };

    /**
     * Refresh-Button-Event
     */
    $('.refresh-button').on('click', function() {
        f.refresh();
    });

    /**
     * Refresh the page once, if there is a dynamic-content-container
     */
    $(function() {
        if (f.dContent.length) {
            f.refresh();
        }
    });

    // init the whole stuff
    f.init();

    setInterval(function() {
        f.loadStatus();
    }, 60000);
}); }(jQuery));
