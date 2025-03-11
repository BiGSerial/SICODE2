<?php

namespace App\Custom;

class RegistroJson
{
    private $last_id;
    private $filepath;
    private $task;
    private $options;
    private $total;
    private $updated;
    private $created;
    private $noteUpdated;
    private $errors;
    private $erroMsg;
    private $datetime_init;


    public function __construct(string $task, $options = null, $total = null, $created = null, $updated = null, $noteUpdated = null)
    {
        $this->last_id = null;
        $this->filepath = base_path('registroUpdate.json');
        $this->task = $task;
        $this->options = $options;
        $this->total = $total;
        $this->created = $created;
        $this->updated = $updated;
        $this->noteUpdated = $noteUpdated;
        $this->errors = 0;
        $this->datetime_init = date('Y-m-d H:i:s');
        $this->erroMsg = [];
    }

    // Getters
    public function getLastId()
    {
        return $this->last_id;
    }


    public function getFilepath()
    {
        return $this->filepath;
    }

    public function getTask()
    {
        return $this->task;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getNoteUpdated()
    {
        return $this->noteUpdated;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getDatetimeInit()
    {
        return $this->datetime_init;
    }


    // Setters
    public function setFilepath($filepath)
    {
        $this->filepath = $filepath;
    }

    public function setTask($task)
    {
        $this->task = $task;
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function setTotal($total)
    {
        $this->total = $total;
    }

    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    public function setCreated($created)
    {
        $this->created = $created;
    }

    public function setNoteUpdated($noteUpdated)
    {
        $this->noteUpdated = $noteUpdated;
    }

    public function setDatetimeInit($datetime_init)
    {
        $this->datetime_init = $datetime_init;
    }

    public function setErrorMessage($erroMsg)
    {
        $this->erroMsg[] = $erroMsg;
        $this->errors++;
    }


    public function save()
    {
        if (!file_exists($this->getFilepath())) {
            // Inicia o array com o primeiro registro e o id 1904, por exemplo
            $newId = 1; // Começa com 1, ou pode iniciar com qualquer outro número

            $registroUpdate[$newId] = [
                'tarefa'     => $this->task,
                'options'    => $this->options,
                'total'      => $this->total,
                'updated'    => $this->updated,
                'created'    => $this->created,
                'noteupdated' => $this->noteUpdated,
                'erros'      => $this->errors,
                'errosMSGs'      => $this->erroMsg,
                'date_inicio' => $this->datetime_init,
                'date_fim'   => date('Y-m-d H:i:s'),
            ];

            // Define o last_id como 1
            $this->last_id = $newId;

        } else {

            $registroUpdate = json_decode(file_get_contents($this->getFilepath()), true);


            $registroUpdate = array_filter($registroUpdate, function ($registro) {
                return strtotime($registro['date_fim']) >= strtotime('-5 days');
            });


            $lastId = !empty($registroUpdate) ? max(array_keys($registroUpdate)) : 0;

            if ($this->last_id !== $lastId) {
                // Define o próximo ID
                $newId = $lastId + 1;
            } else {
                $newId = $this->last_id;
            }



            $registroUpdate[$newId] = [
                'tarefa'     => $this->task,
                'options'    => $this->options,
                'total'      => $this->total,
                'updated'    => $this->updated,
                'created'    => $this->created,
                'noteupdated' => $this->noteUpdated,
                'erros'      => $this->errors,
                'errosMSGs'      => $this->erroMsg,
                'date_inicio' => $this->datetime_init,
                'date_fim'   => date('Y-m-d H:i:s'),
            ];

            // Define o last_id como o novo ID
            $this->last_id = $newId;
        }

        // Salva o conteúdo atualizado no arquivo JSON
        file_put_contents($this->getFilepath(), json_encode($registroUpdate));
    }

}
