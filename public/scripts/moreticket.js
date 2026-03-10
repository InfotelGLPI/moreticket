/**
 * moreticket
 *
 * @param  options
 */
(function ($) {
    $.fn.moreticket = function (options) {

        var object = this;
        init();

        /**
         * Start the plugin
         */
        function init() {
            object.params = new Array();
            object.params['lang'] = '';
            object.params['root_doc'] = '';

            object.countSubmit = 0;

            if (options !== undefined) {
                $.each(options, function (index, val) {
                    if (val != undefined && val != null) {
                        object.params[index] = val;
                    }
                });
            }
        }

        /**
         * moreticket_injectWaitingTicket
         */
        this.moreticket_injectWaitingTicket = function () {

            // On UPDATE/ADD side
            $(document).ready(function () {
                var tickets_id = object.urlParam(window.location.href, 'id');

                // only in ticket form
                if (location.pathname.indexOf('front/ticket.form.php') > 0
                    && (object.params.use_waiting ||object.params.use_solution)) {
                    if (tickets_id == 0 || tickets_id == undefined) {
                        object.createTicket(tickets_id);
                    } else {
                        setTimeout(function () {
                            object.updateTicket(tickets_id);
                        }, 100);
                    }
                }
            });
        };


        //################## On ADD side ################################################################
        /**
         * createTicket
         */
        this.createTicket = function (tickets_id) {
            $.ajax({
                url: object.params.root_doc + '/ajax/ticket.php',
                data: {'tickets_id': tickets_id, 'action': 'showForm', 'type': 'add'},
                type: "POST",
                dataType: "html",
                success: function (response, opts) {
                    $(document).ready(function () {
                        setTimeout(function () {
                            var requester = response;

                            var status_bloc = $("select[name='status']");

                            if (status_bloc !== undefined) {
                                status_bloc.parent().append(requester);

                                // ON DISPLAY : Display or hide waiting type
                                if ($("#moreticket_waiting_ticket") != undefined && $("#moreticket_close_ticket") != undefined) {
                                    // WAITING TICKET
                                    if (status_bloc.val() === object.params.waiting && object.params.use_waiting) {
                                        $("#moreticket_waiting_ticket").css({'display': 'block'});
                                    } else {
                                        $("#moreticket_waiting_ticket").css({'display': 'none'});
                                    }
                                    // CLOSE TICKET
                                    var show_solution = false;
                                    if (object.params.solution_status != null && object.params.solution_status != '') {
                                        var solutionstatus = object.params.solution_status.replace(/&quot;/g,'"');
                                        $.each($.parseJSON(solutionstatus), function (index, val) {
                                            if (index == status_bloc.val()) {
                                                show_solution = true;
                                            }
                                        });
                                    }
                                    if (show_solution && object.params.use_solution) {
                                        $("#moreticket_close_ticket").css({'display': 'block'});
                                    } else {
                                        $("#moreticket_close_ticket").css({'display': 'none'});
                                    }

                                    // ONCLICK : Display or hide waiting type
                                    status_bloc.change(function () {
                                        // WAITING TICKET
                                        if (status_bloc.val() == object.params.waiting && object.params.use_waiting) {
                                            $("#moreticket_waiting_ticket").css({'display': 'block'});
                                        } else {
                                            $("#moreticket_waiting_ticket").css({'display': 'none'});
                                        }

                                        // CLOSE TICKET
                                        var show_solution = false;
                                        if (object.params.solution_status != null && object.params.solution_status != '') {
                                            var solutionstatus = object.params.solution_status.replace(/&quot;/g,'"');
                                            $.each($.parseJSON(solutionstatus), function (index, val) {
                                                if (index == status_bloc.val()) {
                                                    show_solution = true;
                                                }
                                            });
                                        }
                                        if (show_solution && object.params.use_solution) {
                                            $("#moreticket_close_ticket").css({'display': 'block'});
                                        } else {
                                            $("#moreticket_close_ticket").css({'display': 'none'});
                                        }
                                    });
                                }
                            }
                        }, 500);
                    });
                }
            });
        };

        //################## On UPDATE side ################################################################

        /**
         * updateTicket
         *
         * @param tickets_id
         */
        this.updateTicket = function (tickets_id) {
            //Inject Waiting ticket data
            // console.log(tickets_id);

            $.ajax({
                url: object.params.root_doc + '/ajax/ticket.php',
                data: {'tickets_id': tickets_id, 'action': 'showForm', 'type': 'update'},
                type: "POST",
                dataType: "html",
                success: function (response, opts) {
                    $(document).ready(function () {
                        setTimeout(function () {
                            // console.log(response);
                            if ($("#moreticket_waiting_ticket").length != 0) {
                                $("#moreticket_waiting_ticket").remove();
                            }
                            var requester = response;

                            var status_bloc = $("select[name='status']");

                            if (status_bloc != undefined && status_bloc.length != 0) {
                                status_bloc.parent().append(requester);

                                // ON DISPLAY : Display or hide waiting type
                                if ($("#moreticket_waiting_ticket") != undefined) {
                                    // WAITING TICKET
                                    if (status_bloc.val() == object.params.waiting) {
                                        $("#moreticket_waiting_ticket").css({'display': 'block'});
                                    } else {
                                        $("#moreticket_waiting_ticket").css({'display': 'none'});
                                    }

                                    // ONCHANGE : Display or hide waiting type
                                    status_bloc.change(function () {
                                        // WAITING TICKET
                                        if (status_bloc.val() == object.params.waiting) {
                                            $("#moreticket_waiting_ticket").css({'display': 'block'});
                                        } else {
                                            $("#moreticket_waiting_ticket").css({'display': 'none'});
                                        }
                                    });
                                }
                            }
                        }, 500);
                    });
                }
            });
        };

        /**
         * moreticket_urgency
         */
        this.moreticket_urgency = function () {
            // On UPDATE/ADD side
            $(document).ready(function () {
                var tickets_id = object.urlParam(window.location.href, 'id');

                // only in ticket form
                if ((location.pathname.indexOf('front/ticket.form.php') > 0
                        || location.pathname.indexOf('helpdesk.public.php') > 0
                        || location.pathname.indexOf('tracking.injector.php') > 0)
                    && object.params.use_urgency) {
                    if (tickets_id == 0 || tickets_id == undefined) {
                        object.createTicket_urgency(tickets_id);
                    } else {
                        object.updateTicket_urgency(tickets_id);
                    }
                    //else {
                    //     object.updateTicket_urgency(tickets_id);
                    // }
                    // $("#tabspanel + div.ui-tabs").on("tabsload", function () {
                    //    setTimeout(function () {
                    //       if (tickets_id == 0 || tickets_id == undefined) {
                    // object.createTicket_urgency(tickets_id);
                    // }
                    // }, 300);
                    // });
                }

                if ((location.pathname.indexOf('front/newticket.form.php') > 0)
                    && object.params.use_urgency) {
                    if (tickets_id == 0 || tickets_id == undefined) {
                        object.createSCTicket_urgency(tickets_id);
                    }
                }
            });
        };

        this.createSCTicket_urgency = function (tickets_id) {

            $.ajax({
                url: object.params.root_doc + '/ajax/ticket.php',
                data: {
                    tickets_id: tickets_id,
                    action: 'showFormUrgency',
                    type: 'add'
                },
                type: "POST",
                dataType: "html",
                success: function (response) {

                    const blocktoadd = response;

                    // Convert urgency_ids en array
                    let urgency_ids = object.params.urgency_ids;
                    if (typeof urgency_ids === "string") {
                        urgency_ids = urgency_ids.split(',').map(Number);
                    }

                    const radios = $("input[name='urgency']");

                    if (!radios.length) return;

                    // Vérifie si au moins une urgence doit afficher le bloc
                    const shouldDisplay = radios.toArray().some(r =>
                        urgency_ids.includes(parseInt(r.value)) && object.params.use_urgency
                    );

                    if (shouldDisplay) {
                        $("#justification").after(blocktoadd);
                    }

                    function toggleUrgencyBlock() {
                        const selected = parseInt($("input[name='urgency']:checked").val());

                        if (urgency_ids.includes(selected) && object.params.use_urgency) {
                            $("#moreticket_urgency_ticket").css("display", "flex");
                        } else {
                            $("#moreticket_urgency_ticket").hide();
                        }
                    }

                    // Etat initial
                    toggleUrgencyBlock();

                    // Au clic sur un radio
                    radios.on("click", toggleUrgencyBlock);
                }
            });
        };

        this.createTicket_urgency = function (tickets_id) {

            $.ajax({
                url: object.params.root_doc + '/ajax/ticket.php',
                data: {
                    tickets_id: tickets_id,
                    action: 'showFormUrgency',
                    type: 'add'
                },
                type: "POST",
                dataType: "html",

                success: function (response) {

                    const requester = response;

                    // Convert urgency_ids en array
                    let urgency_ids = object.params.urgency_ids;

                    if (typeof urgency_ids === "string") {
                        try {
                            urgency_ids = JSON.parse(urgency_ids);
                        } catch {
                            urgency_ids = urgency_ids.split(',').map(Number);
                        }
                    }

                    const urgency_bloc = $("select[name='urgency']");
                    if (!urgency_bloc.length) return;

                    urgency_bloc.parent().append(requester);

                    const urgencyTicket = $("#moreticket_urgency_ticket");
                    if (!urgencyTicket.length) return;

                    function toggleUrgency() {

                        const value = parseInt(urgency_bloc.val());

                        const show =
                            urgency_ids.includes(value) &&
                            object.params.use_urgency;

                        urgencyTicket.css("display", show ? "flex" : "none");
                    }

                    // Etat initial
                    toggleUrgency();

                    // Au changement du select
                    urgency_bloc.on("change", toggleUrgency);
                }
            });

        };

        this.updateTicket_urgency = function (tickets_id) {

            $.ajax({
                url: object.params.root_doc + '/ajax/ticket.php',
                data: {
                    tickets_id: tickets_id,
                    action: 'showFormUrgency',
                    type: 'update'
                },
                type: "POST",
                dataType: "html",

                success: function (response) {

                    // Supprime l'ancien bloc si présent
                    $("#moreticket_urgency_ticket").remove();

                    setTimeout(function () {

                        const requester = response;

                        // Convert urgency_ids en array
                        let urgency_ids = object.params.urgency_ids;

                        if (typeof urgency_ids === "string") {
                            try {
                                urgency_ids = JSON.parse(urgency_ids);
                            } catch {
                                urgency_ids = urgency_ids.split(',').map(Number);
                            }
                        }

                        const urgency_bloc = $("select[name='urgency']");
                        if (!urgency_bloc.length) return;

                        urgency_bloc.parent().append(requester);

                        const urgencyTicket = $("#moreticket_urgency_ticket");
                        if (!urgencyTicket.length) return;

                        function toggleUrgency() {

                            const value = parseInt(urgency_bloc.val());

                            const show = urgency_ids.includes(value);

                            urgencyTicket.css("display", show ? "block" : "none");
                        }

                        // Etat initial
                        toggleUrgency();

                        // Au changement du select
                        urgency_bloc.on("change", toggleUrgency);

                    }, 100);
                }
            });

        };

        function inarray(value, tab) {
          let response = false;

          if (!Array.isArray(tab)) {
              return false; // s�curit� : si ce n�est pas un tableau, on sort
          }

          $.each(tab, function (key, value2) {
              if (value == value2) {
                  response = true;
                  return false; // break du $.each
              }
          });

          return response;
      };


        /**
         *  Get the form values and construct data url
         *
         * @param object form
         */
        this.getFormData = function (form) {
            if (typeof (form) !== 'object') {
                var form = $("form[name='" + form + "']");
            }

            return object.encodeParameters(form[0]);
        };

        /**
         * Encode form parameters for URL
         *
         * @param array elements
         */
        this.encodeParameters = function (elements) {
            var kvpairs = [];

            $.each(elements, function (index, e) {
                if (e.name != '') {
                    switch (e.type) {
                        case 'radio':
                        case 'checkbox':
                            if (e.checked) {
                                kvpairs.push(encodeURIComponent(e.name) + "=" + encodeURIComponent(e.value));
                            }
                            break;
                        case 'select-multiple':
                            var name = e.name.replace("[", "").replace("]", "");
                            $.each(e.selectedOptions, function (index, option) {
                                kvpairs.push(encodeURIComponent(name + '[' + option.index + ']') + '=' + encodeURIComponent(option.value));
                            });
                            break;
                        default:
                            kvpairs.push(encodeURIComponent(e.name) + "=" + encodeURIComponent(e.value));
                            break;
                    }
                }
            });

            return kvpairs.join("&");
        };

        /**
         * Get url parameter
         *
         * @param string url
         * @param string name
         */
        this.urlParam = function (url, name) {
            var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
            if (results == null || results == undefined) {
                return 0;
            }

            return results[1];
        };

        /**
         * Is IE navigator
         */
        this.isIE = function () {
            var ua = window.navigator.userAgent;
            var msie = ua.indexOf("MSIE ");

            if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {      // If Internet Explorer, return version number
                return true;
            }

            return false;
        };

        return this;
    };
}(jQuery));
