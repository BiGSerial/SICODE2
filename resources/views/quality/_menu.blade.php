{{-- Menu lateral da Qualidade: componente Livewire com badges atualizados por polling. --}}
@livewire('quality.sidebar', ['route' => (string) request()->route()?->getName(), 'tab' => request()->route('tab'), 'fila' => request('fila')], key('quality-sidebar'))
