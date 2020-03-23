<div class="modal fade top" id="modal-issue" tabindex="-1"
     role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-top" role="document">
        <!--Content-->
        <div class="modal-content">
            <!--Header-->
            <div class="modal-header stylish-color-dark white-text">
                <h7 class="mt-1">
                    <i class="fas fa-paper-plane"></i>
                    <span id="issue-title"></span>
                </h7>
                <button type="button" class="close white-text" data-dismiss="modal"
                        aria-label="Close">
                    &times;
                </button>
            </div>

            <!--Body-->
            <div class="modal-body p-4">
                <div class="card">
                    <div class="card-body">
                        <div id="modal-body-issue" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <!--Footer-->
            <div class="modal-footer p-1">
                <button type="button" class="btn btn-orange btn-sm waves-effect waves-light"
                        onclick="$(this).issue();">
                    <i class="fas fa-paper-plane"></i> Issue
                </button>
                <button type="button" class="btn btn btn-light btn-sm waves-effect" data-dismiss="modal">
                    <i class="far fa-window-close"></i> Cancel
                </button>
            </div>
        </div>
        <!--/.Content-->
    </div>
</div>

