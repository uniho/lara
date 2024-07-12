@props([
  'title' => 'ERROR',
  'uniq_class' => '_UC_' . uniqid(),
])

<x-sample.html>
  <div class="{{$uniq_class}}">
    <div class="frame">
      <div class="title">
        {{$title}}      
      </div>
      <div class="message">
        {{$message}}
      </div>
    </div>
  </div>

@push('style')
// <style>

.{{$uniq_class}} {
  .frame {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100vh; height: 100dvh;
    align-items: center;
    justify-content: center;

    .title {
      font-family: Roboto;
      font-size: 30px;
    }

    .message {
      margin: 2rem;
      max-width: 800px;
    }
  }
  
  @color: white; 
  @bgcolor: skyblue; 
  a {
    color: inherit;
    background: linear-gradient(to top,@bgcolor 50%,rgba(255,255,255,0) 50%);
    background-size: 100% 200%;
    background-position: 0 10%;
    background-repeat: no-repeat;
    text-decoration: none;
    transition: background-position .3s cubic-bezier(.64,.09,.08,1), color .3s cubic-bezier(.64,.09,.08,1);
    will-change: background-position, color;

    // タッチデバイスなら hover アニメーションはしないようにする
    // そうしないと、タッチ後に hover の状態で描画されたままてなってしまうため
    @media (hover: hover) and (pointer: fine) {
      &:hover {
        color: @color;
        background-position: 0 100%;
      }
    }
  }
}

// </style>
@endpush

</x-sample.html>  
