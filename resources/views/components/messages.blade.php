<div class="toast-wrapper position-relative" style="z-index: 1050;">
    <div class="toast-container">
        @if($errors?->any())
            @foreach($errors->all() as $error)
                <x-ig::message type="danger" message="{!! $error !!}"/>
            @endforeach
        @endif
        @if(session('success', false))
            @php
            $data = session('success');
            if (!is_array($data)) {
                $data = [$data];
            }
            @endphp
            @foreach ($data as $msg)
                <x-ig::message type="success" message="{!! $msg !!}"/>
            @endforeach
        @endif
    </div>
</div>

