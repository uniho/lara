@props([
  'title' => 'ERROR',
  'message' => 'NO MESSAGE',
  'css_id' => $css->getId(),
])

<x-sample.html>
  <x-slot:title>
    {{ HQ::getenv('CCC::APP_NAME') }} 
  </x-slot>  
  
  <div class="{{$css_id}}">
    <div class="frame">

      @if($title)
      <div class="title">
        {{$title}}      
      </div>
      @endif

      <div class="message">
        {!! Compilers::markdown()->inline($message) !!}
      </div>

    </div>
  </div>

@css($css_id)
<style>

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

    /* タッチデバイスなら hover アニメーションはしないようにする
    そうしないと、タッチ後に hover の状態で描画されたままてなってしまうため */
    @media (hover: hover) and (pointer: fine) {
      &:hover {
        color: @color;
        background-position: 0 100%;
      }
    }
  }

</style>
@endcss

</x-sample.html>  
