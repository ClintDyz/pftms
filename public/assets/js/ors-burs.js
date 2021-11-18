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

    function initializeInputs() {
        $('#sel-uacs-code').change(function() {
            const uacsVals = $(this).val();
            const uacsDescs = $(this).find('option:selected').not('option:disabled').map(function(){
                return $(this).text().trim();
            }).get();
            let uacsDescHTML = '', uacsAmountHTML = '';

            if (!empty(uacsVals) && !empty(uacsDescs)) {
                uacsVals.forEach((uacsID, uacsIndex) => {
                    const uacsDescCode = uacsDescs[uacsIndex];
                    const uacsCode = uacsDescCode.split(" : ")[0];
                    const uacsDesc = uacsDescCode.split(" : ")[1];

                    let description = $(`#uacs_description_${uacsID}`).val();
                    let _uacsID = $(`#uacs_id_${uacsID}`).val();
                    let orsUacsID = $(`#ors_uacs_id_${uacsID}`).val();
                    let amount = $(`#uacs_amount_${uacsID}`).val();

                    description = !empty(description) ? description : uacsDesc;
                    _uacsID = !empty(_uacsID) ? _uacsID : uacsID;
                    orsUacsID = !empty(orsUacsID) ? orsUacsID : '';
                    amount = !empty(amount) ? amount : 0;

                    uacsDescHTML += `
                    <div class="md-form form-sm" id="uacs_description_${uacsIndex}">
                        <input type="text" id="uacs_description_${uacsID}" name="uacs_description[]"
                            class="form-control required" value="${description}">
                        <input type="hidden" id="uacs_id_${uacsID}" name="uacs_id[]" value="${_uacsID}">
                        <input type="hidden" id="ors_uacs_id_${uacsID}" name="ors_uacs_id[]" value="${orsUacsID}">
                        <label for="uacs_description" class="active">
                            <span class="red-text">* </span>
                            <strong>${uacsDescCode}</strong>
                            <a onclick="$(this).deleteUacsItem(
                                '#uacs_description_${uacsIndex}', '#uacs_amount_${uacsIndex}',
                                '${uacsID}'
                               );"
                               class="btn btn-red btn-sm py-0 rounded" >
                                <strong>Delete</strong>
                            </a>
                        </label>
                    </div>
                    `;
                    uacsAmountHTML += `
                    <div class="md-form form-sm" id="uacs_amount_${uacsIndex}">
                        <input type="text" id="uacs_amount_${uacsID}" name="uacs_amount[]"
                            class="form-control required" value="${amount}">
                        <label for="uacs_amount" class="active">
                            <span class="red-text">* </span>
                            <strong>${uacsCode} Amount</strong>
                        </label>
                    </div>
                    `;
                });
            } else {
                uacsDescHTML = '';
                uacsAmountHTML = '';
            }

            $('#uacs-description-segment').html(uacsDescHTML);
            $('#uacs-amount-segment').html(uacsAmountHTML);
        });

        $('#amount').change(function() {
            const amount = $(this).val();
            $('#total').val(amount);
        });
    }

    $.fn.deleteUacsItem = function(elemDescID, elemAmountID, uacsID) {
        $(elemDescID).remove();
        $(elemAmountID).remove();

        //$(`#sel-uacs-code option:selected`).removeAttr('selected');
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

            initializeInputs();
        });
        $("#modal-lg-create").modal({keyboard: false, backdrop: 'static'})
						     .on('shown.bs.modal', function() {
            $('#create-title').html('Create ORS/BURS');
		}).on('hidden.bs.modal', function() {
		    $('#modal-body-create').html('').css('display', 'none');
		});
    }

    $.fn.showCreateDV = function(url) {
        $('#mdb-preloader').css('background', '#000000ab').fadeIn(300);
        $('#modal-body-create').load(url, function() {
            $('#mdb-preloader').fadeOut(300);
            $('.crud-select').materialSelect();
            $(this).slideToggle(500);

            initializeInputs();
        });
        $("#modal-lg-create").modal({keyboard: false, backdrop: 'static'})
						     .on('shown.bs.modal', function() {
            $('#create-title').html('Create DV from ORS/BURS');
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

            initializeInputs();
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
            $('#issue-title').html('Submit ORS/BURS');
            $(this).find('.btn-orange').html('<i class="fas fa-paper-plane"></i> Submit');
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
            $('#issue-back-title').html('Submit Back ORS/BURS');
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
            $('.crud-select').materialSelect();
            $('#type').change(function() {
                $('#serial_no').val($(this).val().split('-')[1]).siblings('label').addClass('active');
            });
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
