<x-guest-layout title="PRF Giving Commitment">
  @push('styles')
  <style>
    label{display:block;font-size:13px;font-weight:600;color:var(--ink);margin:18px 0 7px;}
    label .req{color:var(--lime);}
    input[type=text],input[type=tel],input[type=email],input[type=number],input[type=date]{
      width:100%;padding:11px 13px;background:#fafbfc;
      border:1px solid var(--line);border-radius:3px;color:var(--ink);font-size:15px;font-family:'Inter',sans-serif;
      caret-color:var(--ink);color-scheme:light;
    }
    input::placeholder{color:#6b7280;}
    input:focus{outline:2px solid var(--lime);outline-offset:1px;border-color:var(--lime-dim);}
  .freq-opt:focus-visible{outline:2px solid var(--lime);outline-offset:2px;}
    .hint{font-size:12px;color:var(--muted);margin-top:5px;}
    .freq-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:6px;}
  @media(max-width:420px){.freq-grid{grid-template-columns:1fr;}}
  .freq-opt{
    border:1px solid var(--line);border-radius:3px;padding:10px 12px;cursor:pointer;
    display:flex;justify-content:space-between;align-items:center;font-size:13.5px;
    transition:border-color .15s, background .15s;
    background:#f8fafc;color:var(--ink);
  }
  .freq-opt:hover{border-color:var(--lime-dim);}
  .freq-opt .n{color:var(--muted);font-family:'JetBrains Mono',monospace;font-size:11px;}
  .freq-opt.sel{background:rgba(123,197,63,0.12);border-color:var(--lime-dim);color:var(--lime-dim);}
  .freq-opt.sel .n{color:var(--lime-dim);}
  .impact{
    margin-top:24px;padding:18px 18px 16px;
    background:#f5f7fa;border:1px dashed var(--line);border-radius:3px;
  }
  .impact .label{font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:8px;}
  .impact .big{font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--lime-dim);}
  .impact .small{font-size:12.5px;color:var(--muted);margin-top:4px;}
  button.submit{
    width:100%;margin-top:26px;padding:14px;border:none;border-radius:3px;
    background:var(--lime);color:var(--navy);font-weight:700;font-size:15px;
    cursor:pointer;font-family:'Inter',sans-serif;letter-spacing:.01em;
  }
  button.submit:hover{background:var(--lime-dim);}
  button.submit:disabled{opacity:.55;cursor:default;}
  .err{color:#dc2626;font-size:12.5px;margin-top:8px;display:none;}
  .ferr{color:#dc2626;font-size:12.5px;margin-top:8px;min-height:16px;}
  .btn2{background:#fafbfc;border:1px solid var(--line);color:var(--navy);padding:9px 14px;border-radius:3px;font-size:13px;cursor:pointer;font-family:'Inter',sans-serif;}
  .btn2:hover{border-color:var(--lime-dim);}
  .confirm{text-align:center;padding:20px 10px;}
  .confirm h2{font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--navy);margin:14px 0 8px;}
  .confirm p{color:var(--muted);font-size:14px;}
  .confirm .sum{margin:20px auto 0;max-width:340px;text-align:left;background:#f5f7fa;border:1px solid var(--line);border-radius:3px;padding:16px 18px;font-size:13.5px;}
  .confirm .sum div{display:flex;justify-content:space-between;padding:4px 0;color:var(--muted);}
  .confirm .sum div b{color:var(--navy);font-weight:600;}
  .confirm .tick{font-size:38px;color:var(--lime);text-align:center;animation:tickpop .45s cubic-bezier(.2,.9,.3,1.4);}
  .confirm p b{color:var(--ink);font-weight:600;}
  @keyframes tickpop{0%{transform:scale(.4);opacity:0;}100%{transform:scale(1);opacity:1;}}
</style>
  @endpush

  <div class="wrap">
    <div class="eyebrow">Parkroad Fellowship</div>
    <h1>Make your <em>giving commitment</em></h1>
    <p class="lede">Your pledge helps us plan the mission work ahead. You will only receive updates if you share an email.</p>
    <div class="verse">
      "Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion."
      <span>2 CORINTHIANS 9:7</span>
    </div>

    <livewire:pledge-form />
  </div>
</x-guest-layout>
