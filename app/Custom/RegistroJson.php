<?php

namespace App\Custom;

class RegistroJson
{
    protected $filePath;

    protected $data;

    protected $record;

    protected $perPage;

    protected $currentPage;

    protected $limit;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;

        if (!file_exists($this->filePath)) {
            $this->createFile();
        }

        $this->data = $this->all();
    }

    private function createFile()
    {
        $defaultData = [];
        file_put_contents($this->filePath, json_encode($defaultData, JSON_PRETTY_PRINT));
    }

    public function all()
    {
        return json_decode(file_get_contents($this->filePath), true);
    }

    private function save()
    {
        file_put_contents($this->filePath, json_encode($this->data, JSON_PRETTY_PRINT));
    }

    public function select($fields = [])
    {
        if (empty($fields)) {
            return $this;
        }

        $this->data = array_map(function ($item) use ($fields) {
            return array_intersect_key($item, array_flip($fields));
        }, $this->data);

        return $this;
    }

    public function find($id)
    {
        $data = $this->all();

        foreach ($data as $item) {
            if ($item['id'] == $id) {
                return $this->model($id);
            }
        }

        return null;
    }

    public function delete($id)
    {
        foreach ($this->data as $key => $item) {
            if ($item['id'] == $id) {
                unset($this->data[$key]);
                $this->save();

                break;
            }
        }
    }

    public function create($newData)
    {
        $newData['id'] = $this->generateId();
        $this->data[]  = $newData;
        $this->save();

        return $this->find($newData['id']);
    }

    public function model($id)
    {
        $data = $this->find($id);

        if ($data) {
            return new RegistroModel($data, $this);
        }

        return null;
    }

    public function update($id, $updatedData)
    {
        foreach ($this->data as $key => $item) {
            if ($item['id'] == $id) {
                $this->data[$key] = array_merge($item, $updatedData);
                $this->save();

                break;
            }
        }
    }

    public function where($field, $operator, $value = null)
    {
        if (func_num_args() == 2) {
            $value    = $operator;
            $operator = '=';
        }

        $this->data = array_filter($this->data, function ($item) use ($field, $operator, $value) {
            if (!isset($item[$field])) {
                return false;
            }

            switch ($operator) {
                case '>':
                    return $item[$field] > $value;
                case '<':
                    return $item[$field] < $value;
                case '>=':
                    return $item[$field] >= $value;
                case '<=':
                    return $item[$field] <= $value;
                case '!=':
                    return $item[$field] != $value;
                case '=':
                default:
                    return $item[$field] == $value;
            }
        });

        return $this;
    }

    public function first()
    {
        $record = reset($this->data);

        return $record ? $this->model($record['id']) : null;
    }

    public function __get($name)
    {
        if ($this->record && array_key_exists($name, $this->record)) {
            return $this->record[$name];
        }

        return false;
    }

    public function count()
    {
        return count($this->data);
    }

    public function orderBy($field, $direction = 'ASC')
    {
        usort($this->data, function ($a, $b) use ($field, $direction) {
            if (!isset($a[$field]) || !isset($b[$field])) {
                return 0;
            }

            if ($a[$field] == $b[$field]) {
                return 0;
            }

            if ($direction === 'DESC') {
                return ($a[$field] < $b[$field]) ? 1 : -1;
            }

            return ($a[$field] < $b[$field]) ? -1 : 1;
        });

        return $this;
    }

    public function paginate($perPage = 15)
    {
        $this->perPage     = $perPage;
        $this->currentPage = 1;

        return $this;
    }

    public function page($page)
    {
        $this->currentPage = $page;

        return $this;
    }

    public function paginateData()
    {
        $start = ($this->currentPage - 1) * $this->perPage;

        return array_slice($this->data, $start, $this->perPage);
    }

    public function get()
    {
        $models = [];

        foreach ($this->data as $record) {
            $models[] = $this->model($record['id']);
        }

        return $models;
    }

    public function links()
    {
        if (!$this->perPage) {
            return null;
        }

        $totalPages = ceil(count($this->data) / $this->perPage);
        $links      = [];

        for ($i = 1; $i <= $totalPages; $i++) {
            $links[] = "Página {$i}";
        }

        return implode(' | ', $links);
    }

    private function generateId()
    {
        return time();
    }
}

class RegistroModel
{
    protected $data;

    protected $parent;

    public function __construct($data, RegistroJson $parent)
    {
        $this->data   = $data;
        $this->parent = $parent;
    }

    public function update($updatedData)
    {
        $this->data = array_merge($this->data, $updatedData);

        return $this;
    }

    public function save()
    {
        $this->parent->update($this->data['id'], $this->data);

        return $this;
    }

    public function delete()
    {
        $this->parent->delete($this->data['id']);
    }

    // Adicione outros métodos conforme necessário
}
