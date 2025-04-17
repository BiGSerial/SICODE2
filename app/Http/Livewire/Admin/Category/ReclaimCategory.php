<?php

namespace App\Http\Livewire\Admin\Category;

use App\Models\Subcategory;
use Livewire\Component;

class ReclaimCategory extends Component
{
    public $category_id;
    public $subcategory_id;

    public $category;
    public $subcategory;

    protected $listeners = [
        'confirmDeleteCategory',
        'confirmDeleteSubCategory',
    ];

    public function getCategoriesProperty()
    {
        return \App\Models\Category::orderBy('name', 'ASC')->get();
    }

    public function getSubcategoriesProperty()
    {
        return \App\Models\Subcategory::where('category_id', $this->category_id)->orderBy('name', 'asc')->get();
    }

    public function AddCategory()
    {
        $this->validate([
            'category' => 'required',
        ]);
        try {
            \App\Models\Category::updateOrCreate(['name' => mb_strToUpper(trim($this->category))], [
                'name' => mb_strToUpper(trim($this->category)),
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Categoria Adicionada',
                'timer'    => 2500,
            ]);

            return;

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro Ao adicionar Categoria',
                'timer'    => 2500,
            ]);
            return;
        }
    }

    public function AddSubCategory()
    {
        $this->validate([
            'subcategory' => 'required',
            'category_id' => 'required'
        ]);

        try {
            \App\Models\Subcategory::updateOrCreate([
                'category_id' => $this->category_id,
                'name' => mb_strToUpper(trim($this->subcategory))
            ], [
                'name' => mb_strToUpper(trim($this->subcategory)),
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SubCategoria Adicionada',
                'timer'    => 2500,
            ]);

            return;

            $this->subcategory = null;

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro Ao adicionar SubCategoria',
                'timer'    => 2500,
            ]);
            return;
        }
    }

    public function setNeedfile(Subcategory $subcategory)
    {
        $subcategory->needFile = !$subcategory->needFile;
        $subcategory->save();
    }


    public function RemoveCategory($category_id)
    {
        // $this->validate([
        //     'category' => 'required',
        // ]);

        if (\App\Models\Category::where('id', $category_id)->first()->Subcategories->isEmpty()) {
            try {
                \App\Models\Category::where('id', $category_id)->first()->delete();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Categoria Removida',
                    'timer'    => 2500,
                ]);

                return;

            } catch (\Throwable $th) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Erro Ao Remover Categoria',
                    'timer'    => 2500,
                ]);
                return;
            }
        } else {

            $this->category_id = $category_id;

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Remover Categoria',
                'msg'           => "Essa ação removerá também os valores associados a subcategorias. Deseja continuar?</p> ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Remova!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirmDeleteCategory',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhum usuário alterado.',

            ]);

            return;
        }
    }


    public function RemoveSubCategory($subcategory_id)
    {
        // $this->validate([
        //     'category' => 'required',
        // ]);

        if (\App\Models\Subcategory::where('id', $subcategory_id)->first()->Reclaims->isEmpty()) {
            try {
                \App\Models\Subcategory::where('id', $subcategory_id)->first()->delete();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Categoria Removida',
                    'timer'    => 2500,
                ]);

                return;

            } catch (\Throwable $th) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Erro Ao Remover Categoria',
                    'timer'    => 2500,
                ]);
                return;
            }
        } else {

            $this->subcategory_id = $subcategory_id;

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Remover SubCategoria',
                'msg'           => "Essa ação removerá também os valores associados a subcategorias. Deseja continuar?</p> ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Remova!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirmDeleteSubCategory',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhum usuário alterado.',

            ]);

            return;
        }
    }


    public function confirmDeleteCategory()
    {

        if ($this->category_id) {
            try {
                \App\Models\Category::where('id', $this->category_id)->delete();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Categoria Removida',
                    'timer'    => 2500,
                ]);

                return;

            } catch (\Throwable $th) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Erro Ao Remover Categoria',
                    'timer'    => 2500,
                ]);
                return;
            }
        }



    }

    public function confirmDeleteSubCategory()
    {

        if ($this->subcategory_id) {
            try {
                \App\Models\Subcategory::where('id', $this->subcategory_id)->delete();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Categoria Removida',
                    'timer'    => 2500,
                ]);

                return;

            } catch (\Throwable $th) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Erro Ao Remover Categoria',
                    'timer'    => 2500,
                ]);
                return;
            }
        }



    }


    public function render()
    {
        return view('livewire.admin.category.reclaim-category', [
            'categories' => $this->categories,
            'subcategories' => $this->subcategories,
        ]);
    }
}
