<div class="row">
    <div class="col-md-12">
        <button class="btn btn-sm btn-block btn-link"
                onclick="$(this).refreshRemarks('{{ route('proc-ors-burs-show-remarks', ['id' => $id]) }}');">
                <i class="fas fa-sync"></i> Refresh
        </button>
    </div>
</div>

<div class="table-responsive border m-0 p-0" style="height: 350px;">
    <table class="table table-sm table-bordered m-0 p-0">
        @if (count($docRemarks) > 0)
            @foreach ($docRemarks as $itemCtr => $item)
                @if (!empty($item->remarks))
        <tr>
            <td>
                <p class="p-0">
                    From : {{ Auth::user()->getEmployee($item->emp_from)->name }}<br>
                    <small class="grey-text">
                        <i class="far fa-calendar-alt"></i> {{ $item->logged_at }}
                    </small><br><br>
                    <i class="far fa-comment-dots"></i> {{ $item->remarks }}<br>
                </p>
            </td>
        </tr>
                @endif
            @endforeach
        @endif
    </table>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="md-form">
            <textarea id="message" class="md-textarea form-control required"
                      name="message" rows="3"></textarea>
            <label for="message">
                <i class="fas fa-comment-dots"></i> Type your message here
            </label>
        </div>
        <button class="btn btn-sm btn-block btn-outline-mdb-color"
                onclick="$(this).storeRemarks('{{ route('proc-ors-burs-store-remarks', ['id' => $id]) }}',
                                              '{{ route('proc-ors-burs-show-remarks', ['id' => $id]) }}');">
            <i class="fas fa-location-arrow"></i> Send
        </button>
    </div>
</div>
