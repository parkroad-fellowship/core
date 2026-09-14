<div>
  <div class="card" id="pledgeview" wire:show="!submitted">
    <div class="card-title">Giving Commitment</div>
    <div class="card-sub">Fill this in and the treasurer's desk will keep track of it for you.</div>

    <form wire:submit="submit">
      <label for="f_name">Your name <span class="req">*</span></label>
      <input type="text" id="f_name" placeholder="e.g. Jane Doe" autocomplete="name" wire:model.blur="name">
      <div class="ferr" role="alert" aria-live="assertive">@error('name') {{ $message }} @enderror</div>

      <label for="f_email">Email address</label>
      <input type="email" id="f_email" placeholder="you@example.com (optional, for updates)" autocomplete="email" wire:model.blur="email">
      <div class="hint">Only used for giving updates and reminders. Never shared.</div>
      <div class="ferr" role="alert" aria-live="assertive">@error('email') {{ $message }} @enderror</div>

      <label for="f_phone">Phone / WhatsApp</label>
      <input type="tel" id="f_phone" placeholder="e.g. 0712 345 678 (optional)" autocomplete="tel" wire:model.blur="phone">
      <div class="ferr" role="alert" aria-live="assertive">@error('phone') {{ $message }} @enderror</div>

      <label for="f_amount">Amount <span class="req">*</span></label>
      <input type="number" id="f_amount" placeholder="e.g. 50000" min="1" step="1" wire:model.live="amount">
      <div class="hint">Amounts in Kenyan Shillings (KES).</div>
      <div class="ferr" role="alert" aria-live="assertive">@error('amount') {{ $message }} @enderror</div>

      <label>How often? <span class="req">*</span></label>
      <div class="freq-grid" role="radiogroup" aria-label="Pledge frequency">
        @foreach ($this->frequencies() as $option)
          <button
            type="button"
            class="freq-opt @if ($frequency === $option['value']) sel @endif"
            role="radio"
            aria-checked="{{ ($frequency === $option['value']) ? 'true' : 'false' }}"
            wire:click="$set('frequency', {{ $option['value'] }})"
          >
            <span>{{ $option['label'] }}</span>
            <span class="n">@if ($option['value'] === 0)&times;1 @elseif($option['value'] === 1)/ mo @elseif($option['value'] === 3)/ 3mo @else / yr @endif</span>
          </button>
        @endforeach
      </div>
      <div class="ferr" role="alert" aria-live="assertive">@error('frequency') {{ $message }} @enderror</div>

      <label for="f_date">Start date</label>
      <input type="date" id="f_date" wire:model="startDate">
      <div class="ferr" role="alert" aria-live="assertive">@error('startDate') {{ $message }} @enderror</div>
      @if ($this->impact())
        <div class="impact">
          <div class="label">Your impact</div>
          <div class="big">{{ $this->impact() }}</div>
          <div class="small">@if ($frequency === 0) A one-time annual gift. @else {{ number_format((float) $amount) }} every @if ($frequency === 1) month @else {{ $frequency }} months @endif. @endif</div>
        </div>
      @endif

      <button class="submit" type="submit" wire:loading.attr="disabled">
        <span wire:loading.remove>Make my commitment</span>
        <span wire:loading>Saving...</span>
      </button>
    </form>
  </div>

  <div class="card confirm" id="confirmview" wire:show="submitted" style="display:none;">
    <div class="tick" aria-hidden="true">&#10003;</div>
    <h2>Thank you for your commitment</h2>
    <p>Your pledge is recorded. @if (filled($summary['email'] ?? null)) We'll send a confirmation to <b>{{ $summary['email'] }}</b>. @else If you left an email, we'll send you a confirmation there. @endif</p>
    <div class="sum">
      <div><span>Name</span><b>{{ $summary['name'] ?? '' }}</b></div>
      <div><span>Amount</span><b>KES {{ $summary['amount'] ?? '' }}</b></div>
      <div><span>Frequency</span><b>{{ $summary['frequency'] ?? '' }}</b></div>
      <div><span>Start date</span><b>{{ $summary['start_date'] ?? '' }}</b></div>
    </div>
    <button class="btn2" type="button" wire:click="startOver" style="margin-top:22px;">Make another pledge</button>
  </div>
</div>
