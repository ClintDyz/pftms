$(function() {
    const template = '<div class="tooltip md-tooltip">' +
                     '<div class="tooltip-arrow md-arrow"></div>' +
                     '<div class="tooltip-inner md-inner stylish-color"></div></div>';

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.fn.showRemarks = function(url) {
        $('#mdb-preloader').css('background', '#000000ab').fadeIn(300);
        $('#modal-body-show').load(url, function() {
            $('#mdb-preloader').fadeOut(300);
            $(this).slideToggle(500);
        });
        $("#modal-show").modal({keyboard: false, backdrop: 'static'})
						.on('shown.bs.modal', function() {
            $('#show-title').html('View Remarks');
		}).on('hidden.bs.modal', function() {
		    $('#modal-body-show').html('').css('display', 'none');
		});
    }

    function sendRemarks(url, refreshURL, formData) {
        $.ajax({
		    url: url,
            type: 'POST',
            processData: false,
            contentType: false,
            data: formData,
		    success: function(response) {
                $('#modal-body-show').load(refreshURL, function() {
                    $('#mdb-preloader').fadeOut(300);
                });
            },
            fail: function(xhr, textStatus, errorThrown) {
                sendRemarks(url, refreshURL, formData);
		    },
		    error: function(data) {
                sendRemarks(url, refreshURL, formData);
		    }
        });
    }

    $.fn.refreshRemarks = function(refreshURL) {
        $('#mdb-preloader').css('background', '#000000ab').fadeIn(300);
        $('#modal-body-show').load(refreshURL, function() {
            $('#mdb-preloader').fadeOut(300);
        });
    }

    $.fn.storeRemarks = function(url, refreshURL) {
        let formData = new FormData();
        const message = $('#message').val(),
              withError = inputValidation(false);

		if (!withError) {
            $('#mdb-preloader').css('background', '#000000ab').fadeIn(300);
            formData.append('message', message);
			sendRemarks(url, refreshURL, formData);
        }
    }

    $.fn.showCreate = function(url) {
        $('#mdb-preloader').css('background', '#000000ab').fadeIn(300);
        $('#modal-body-create').load(url, function() {
            $('#mdb-preloader').fadeOut(300);
            $('.crud-select').materialSelect();
            $(this).slideToggle(500);

            $('#amount').change(function() {
                const amount = $(this).val();
                $('#total').val(amount);
            });
        });
        $("#modal-sm-create").modal({keyboard: false, backdrop: 'static'})
						     .on('shown.bs.modal', function() {
            $('#create-title').html('Create ORS/BURS');
		}).on('hidden.bs.modal', function() {
		    $('#modal-body-create').html('').css('display', 'none');
		});
    }

    $.fn.store = function() {
        const withError = inputValidation(false);

		if (!withError) {
			$('#form-store').submit();
        }
    }

    $.fn.showEdit = function(url) {
        $('#mdb-preloader').css('background', '#000000ab').fadeIn(300);
        $('#modal-body-edit').load(url, function() {
            $('#mdb-preloader').fadeOut(300);
            $('.crud-select').materialSelect();
            $(this).slideToggle(500);

            $('#amount').change(function() {
                const amount = $(this).val();
                $('#total').val(amount);
            });
        });
        $("#modal-lg-edit").modal({keyboard: false, backdrop: 'static'})
						   .on('shown.bs.modal', function() {
            $('#edit-title').html('Update ORS/BURS');
		}).on('hidden.bs.modal', function() {
            $('#modal-body-edit').html('').css('display', 'none');
		});
    }

    $.fn.update = function() {
        const withError = inputValidation(false);

		if (!withError) {
			$('#form-update').submit();
		}
    }

    $.fn.showDelete = function(url, name) {
		$('#modal-body-delete').html(`Are you sure you want to delete this ${name} `+
                                     `document?`);
        $("#modal-delete").modal({keyboard: false, backdrop: 'static'})
						  .on('shown.bs.modal', function() {
            $('#delete-title').html('Delete ORS/BURS');
            $('#form-delete').attr('action', url);
		}).on('hidden.bs.modal', function() {
             $('#modal-delete-body').html('');
             $('#form-delete').attr('action', '#');
		});
    }

    $.fn.delete = function() {
        $('#form-delete').submit();
    }

	$.fn.showIssue = function(url) {
        $('#mdb-preloader').css('background', '#000000ab').fadeIn(300);
        $('#modal-body-issue').load(url, function() {
            $('#mdb-preloader').fadeOut(300);
            $(this).slideToggle(500);
        });
        $("#modal-issue").modal({keyboard: false, backdrop: 'static'})
						 .on('shown.bs.modal', function() {
            $('#issue-title').html('Issue ORS/BURS');
		}).on('hidden.bs.modal', function() {
            $('#modal-body-issue').html('').css('display', 'none');
		});
    }

    $.fn.issue = function() {
        $('#form-issue').submit();
    }

    $.fn.showReceive = function(url) {
        $('#mdb-preloader').css('background', '#000000ab').fadeIn(300);
        $('#modal-body-receive').load(url, function() {
            $('#mdb-preloader').fadeOut(300);
            $(this).slideToggle(500);
        });
        $("#modal-receive").modal({keyboard: false, backdrop: 'static'})
						   .on('shown.bs.modal', function() {
            $('#receive-title').html('Receive ORS/BURS');
		}).on('hidden.bs.modal', function() {
            $('#modal-body-receive').html('').css('display', 'none');
		});
    }

    $.fn.receive = function() {
        $('#form-receive').submit();
    }

    $.fn.showIssueBack = function(url) {
        $('#mdb-preloader').css('background', '#000000ab').fadeIn(300);
        $('#modal-body-issue-back').load(url, function() {
            $('#mdb-preloader').fadeOut(300);
            $(this).slideToggle(500);
        });
        $("#modal-issue-back").modal({keyboard: false, backdrop: 'static'})
						      .on('shown.bs.modal', function() {
            $('#issue-back-title').html('Issue Back ORS/BURS');
		}).on('hidden.bs.modal', function() {
            $('#modal-body-issue-back').html('').css('display', 'none');
		});
    }

    $.fn.issueBack = function() {
        $('#form-issue-back').submit();
    }

    $.fn.showReceiveBack = function(url) {
        $('#mdb-preloader').css('background', '#000000ab').fadeIn(300);
        $('#modal-body-receive-back').load(url, function() {
            $('#mdb-preloader').fadeOut(300);
            $(this).slideToggle(500);
        });
        $("#modal-receive-back").modal({keyboard: false, backdrop: 'static'})
						        .on('shown.bs.modal', function() {
            $('#receive-back-title').html('Receive Back ORS/BURS');
		}).on('hidden.bs.modal', function() {
            $('#modal-body-receive-back').html('').css('display', 'none');
		});
    }

    $.fn.receiveBack = function() {
        $('#form-receive-back').submit();
    }

    $.fn.showObligate = function(url) {
        $('#mdb-preloader').css('background', '#000000ab').fadeIn(300);
        $('#modal-body-obligate').load(url, function() {
            $('#mdb-preloader').fadeOut(300);
            $(this).slideToggle(500);
        });
        $("#modal-obligate").modal({keyboard: false, backdrop: 'static'})
						        .on('shown.bs.modal', function() {
            $('#obligate-title').html('Obligate ORS/BURS');
		}).on('hidden.bs.modal', function() {
            $('#modal-body-obligate').html('').css('display', 'none');
		});
    }

    $.fn.obligate = function() {
        const withError = inputValidation(false);

        if (!withError) {
            $('#form-obligate').submit();
        }
    }

    $('.material-tooltip-main').tooltip({
        template: template
    });
});