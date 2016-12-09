function moreticket(params) {

    var root_doc = params.root_doc;
    var waiting = params.waiting;
    var closed = params.closed;
    var use_waiting = params.use_waiting;
    var use_solution = params.use_solution;
    var solution_status = params.solution_status;

    //################## On ADD side ################################################################
    $(document).ready(function () {
        // only in ticket form
        if (location.pathname.indexOf('ticket.form.php') > 0
            && (use_solution || use_waiting)) {

            $.urlParam = function (name) {
                var results = new RegExp('[\?&amp;]' + name + '=([^&amp;#]*)').exec(window.location.href);
                if (results != null) {
                    return results[1] || 0;
                }

                return undefined;
            }
            // get tickets_id
            var tickets_id = 0;
            if ($.urlParam('id') != undefined) {
                tickets_id = $.urlParam('id');
            }

            if (tickets_id > 0)
                return;

            // Launched on each complete Ajax load 
            $(document).ajaxComplete(function (event, xhr, option) {
                setTimeout(function () {
                    // We execute the code only if the ticket form display request is done 
                    if (option.url != undefined) {
                        var ajaxTab_param, tid;
                        var paramFinder = /[?&]?_glpi_tab=([^&]+)(&|$)/;

                        // We find the name of the current tab
                        ajaxTab_param = paramFinder.exec(option.url);

                        // Get the right tab
                        if (ajaxTab_param != undefined
                            && (ajaxTab_param[1] == "Ticket$main")) {

                            //Inject Waiting ticket data
                            $.ajax({
                                url: root_doc + '/plugins/moreticket/ajax/ticket.php',
                                data: {'tickets_id': tickets_id, 'action': 'showForm', 'type': 'add'},
                                type: "POST",
                                dataType: "html",
                                success: function (response, opts) {
                                    var requester = response;

                                    var status_bloc = $("select[name='status']");

                                    if (status_bloc != undefined) {
                                        status_bloc.parent().append(requester);

                                        // ON DISPLAY : Display or hide waiting type
                                        if ($("#moreticket_waiting_ticket") != undefined && $("#moreticket_close_ticket") != undefined) {
                                            // WAITING TICKET 
                                            if (status_bloc.val() == waiting && use_waiting) {
                                                $("#moreticket_waiting_ticket").css({'display': 'block'});
                                            } else {
                                                $("#moreticket_waiting_ticket").css({'display': 'none'});
                                            }
                                            // CLOSE TICKET 
                                            var show_solution = false;
                                            if (solution_status != null && solution_status != '') {
                                                $.each($.parseJSON(solution_status), function (index, val) {
                                                    if (index == status_bloc.val()) {
                                                        show_solution = true;
                                                    }
                                                });
                                            }
                                            if (show_solution && use_solution) {
                                                $("#moreticket_close_ticket").css({'display': 'block'});
                                            } else {
                                                $("#moreticket_close_ticket").css({'display': 'none'});
                                            }

                                            // ONCLICK : Display or hide waiting type
                                            status_bloc.change(function () {
                                                // WAITING TICKET 
                                                if (status_bloc.val() == waiting && use_waiting) {
                                                    $("#moreticket_waiting_ticket").css({'display': 'block'});
                                                } else {
                                                    $("#moreticket_waiting_ticket").css({'display': 'none'});
                                                }

                                                // CLOSE TICKET
                                                var show_solution = false;
                                                if (solution_status != null && solution_status != '') {
                                                    $.each($.parseJSON(solution_status), function (index, val) {
                                                        if (index == status_bloc.val()) {
                                                            show_solution = true;
                                                        }
                                                    });
                                                }
                                                if (show_solution && use_solution) {
                                                    $("#moreticket_close_ticket").css({'display': 'block'});
                                                } else {
                                                    $("#moreticket_close_ticket").css({'display': 'none'});
                                                }
                                            });
                                        }
                                    }
                                }
                            });
                        }
                    }
                }, 200);
            });
        }
    });

    //################## On UPDATE side ################################################################
    $(document).ready(function () {
        // only in ticket form
        if (location.pathname.indexOf('ticket.form.php') > 0
            && use_waiting) {

            $.urlParam = function (name, path) {
                var results = new RegExp('[\?&]?' + name + '=([^&#]*)').exec(path);
                if (results == null || results == undefined) {
                    return 0;
                }

                return results[1];
            }
            // get tickets_id
            var tickets_id = 0;
            if ($.urlParam('id', window.location.href) != undefined) {
                tickets_id = $.urlParam('id', window.location.href);
            }

            if (tickets_id == 0 || tickets_id == undefined)
                return;

            // Launched on each complete Ajax load 
            $(document).ajaxComplete(function (event, xhr, option) {
//                setTimeout(function () {
                // We execute the code only if the ticket form display request is done 
                if (option.url != undefined) {
                    var ajaxTab_param, tid;
                    var paramFinder = /[?&]?_glpi_tab=([^&]+)(&|$)/;

                    // We find the name of the current tab
                    ajaxTab_param = paramFinder.exec(option.url);

                    // Get the right tab
                    if (ajaxTab_param != undefined
                        && (ajaxTab_param[1] == "Ticket$main" || ajaxTab_param[1] == "-1")) {
                        //Inject Waiting ticket data
                        $.ajax({
                            url: root_doc + '/plugins/moreticket/ajax/ticket.php',
                            data: {'tickets_id': tickets_id, 'action': 'showForm', 'type': 'update'},
                            type: "POST",
                            dataType: "html",
                            success: function (response, opts) {
                                if ($("#moreticket_waiting_ticket").length != 0) {
                                    $("#moreticket_waiting_ticket").remove();
                                }
                                var requester = response;

                                var status_bloc = $("select[name='status']");

                                if (status_bloc != undefined) {
                                    status_bloc.parent().append(requester);

                                    // ON DISPLAY : Display or hide waiting type
                                    if ($("#moreticket_waiting_ticket") != undefined) {
                                        // WAITING TICKET
                                        if (status_bloc.val() == waiting) {
                                            $("#moreticket_waiting_ticket").css({'display': 'block'});
                                        } else {
                                            $("#moreticket_waiting_ticket").css({'display': 'none'});
                                        }

                                        // ONCHANGE : Display or hide waiting type
                                        status_bloc.change(function () {
                                            // WAITING TICKET 
                                            if (status_bloc.val() == waiting) {
                                                $("#moreticket_waiting_ticket").css({'display': 'block'});
                                            } else {
                                                $("#moreticket_waiting_ticket").css({'display': 'none'});
                                            }
                                        });
                                    }
                                }
                            }
                        });

                    } else if (option.url.indexOf("ajax/timeline.php") > 0
                        && ($.urlParam('type', option.data) == 'TicketTask' || $.urlParam('type', option.data) == 'TicketFollowup')) {
                        //Inject Waiting ticket data
                        $.ajax({
                            url: root_doc + '/plugins/moreticket/ajax/ticket.php',
                            data: {'tickets_id': tickets_id, 'action': 'showForm', 'type': 'update'},
                            type: "POST",
                            dataType: "html",
                            success: function (response, opts) {
                                var requester = response;
                                var status_bloc = $("#x-split-button");

                                if (status_bloc != undefined) {
                                    if ($("#moreticket_waiting_ticket").length != 0) {
                                        $("#moreticket_waiting_ticket").remove();
                                    }
                                    status_bloc.parent().append(requester);
                                    $("#x-split-button input[type='radio']").each(function (index, value) {
                                        if ($(this).is(':checked')) {
                                            if ($(this).val() == 4) {
                                                $("#moreticket_waiting_ticket").css({
                                                    'display': 'block',
                                                    'clear': 'both',
                                                    'text-align': 'center'
                                                });
                                            } else {
                                                $("#moreticket_waiting_ticket").css({'display': 'none'});
                                            }
                                        }
                                    });
                                    $("#x-split-button input[type='radio']").change(function () {
                                        if ($(this).is(':checked')) {
                                            if ($(this).val() == 4) {
                                                $("#moreticket_waiting_ticket").css({
                                                    'display': 'block',
                                                    'clear': 'both',
                                                    'text-align': 'center'
                                                });
                                            } else {
                                                $("#moreticket_waiting_ticket").css({'display': 'none'});
                                            }
                                        }
                                    });
                                }
                            }
                        });


                    }
                }
//                }, 200);
            });
        }
    });

}