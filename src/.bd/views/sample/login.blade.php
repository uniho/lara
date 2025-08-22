<x-sample.html>

  <x-slot:title>
    {{ HQ::getenv('CCC::APP_NAME') }} 
  </x-slot>  

  <x-slot:header>
  </x-slot>  

  {{-- slot --}}
  <form method="POST">
    @csrf
    <div>{{ HQ::getenv('CCC::APP_NAME') }}</div>
    <input name="secret" type="password" />
    <button type="submit">
      LOGIN
    </button>
  </form>

</x-sample.html>
