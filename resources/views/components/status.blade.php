@php
    $statusValue = trim((string) $value);
    $isCallNotConnected = in_array(strtolower($statusValue), ['call not connected', 'call not conected'], true);
    $displayValue = $isCallNotConnected ? strtoupper($statusValue) : $statusValue;
@endphp
<i class='fa fa-circle mr-2 text-{{ $color }}' @if (isset($style) && $style != '') style="{{ $style }}" @endif></i><strong style="font-weight:900; text-transform:uppercase; letter-spacing:0.03em;">{{ $displayValue }}</strong>
