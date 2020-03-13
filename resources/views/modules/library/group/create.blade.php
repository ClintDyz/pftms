<form id="form-store" method="POST" action="{{ route('emp-group-store') }}">
    @csrf

    <div class="md-form">
        <input type="text" id="group-name" class="form-control required"
               name="group_name">
        <label for="group-name">
            Employee Group Name <span class="red-text">*</span>
        </label>
    </div>

    <span class="d-block text-center">
        <strong class="text-black-50">* EMPLOYEE DIVISION ACCESS *</strong>
        <input type="hidden" name="module_access" id="json-access">
    </span>
    <hr class="my-2">

    @if (count($divisions) > 0)
    <div id="division-menu">
        <div class="custom-control custom-checkbox ">
            <input type="checkbox" class="custom-control-input" id="sel-all">
            <label class="custom-control-label" for="sel-all">
                <small><em>-- Select all division --</em></small>
            </label>
        </div>

        @foreach ($divisions as $ctr => $div)
        <div class="custom-control custom-checkbox ">
            <input type="checkbox" class="custom-control-input" id="chk-{{ $ctr }}"
                value="{{ $div->id }}" name="divisions[]">
            <label class="custom-control-label" for="chk-{{ $ctr }}">
                {!! $div->division_name !!}
            </label>
        </div>
        @endforeach
    </div>
    <hr class="my-2">
    @endif

    <div class="md-form">
        <select class="mdb-select md-form" searchable="Search here.."
                name="group_head">
            <option value="" disabled selected>Choose group head</option>
            <option>-- None --</option>

            @if (count($employees) > 0)
                @foreach ($employees as $emp)
            <option value="{{ $emp->id }}">
                {!! $emp->name !!} [{!! $emp->position !!}]
            </option>
                @endforeach
            @endif
        </select>
    </div>
</form>
