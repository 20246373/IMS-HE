@php($ids = ['sss_no' => 'sss', 'philhealth_no' => 'philhealth', 'pagibig_no' => 'pagibig', 'tin_no' => 'tin'])
@foreach($ids as $field => $type)
 <label>{{ \App\Support\GovernmentId::LABELS[$type] }} <span class="opt">(optional)</span>
  <input name="{{ $field }}" value="{{ old($field, $employee?->$field) }}" maxlength="30" placeholder="{{ \App\Support\GovernmentId::EXAMPLES[$type] }}"></label>
@endforeach
