<?php

namespace App\PDF\Gerador;

class SicodePdf
{
    protected $adress_cliente;

    protected $name_client;

    protected $note;

    protected $ordem = [];

    protected $obs_text;

    protected $text;

    /**
     * Get the value of adress_cliente
     */
    public function getAdress_cliente()
    {
        return $this->adress_cliente;
    }

    /**
     * Set the value of adress_cliente
     *
     * @return self
     */
    public function setAdress_cliente($adress_cliente)
    {
        $this->adress_cliente = $adress_cliente;

        return $this;
    }

    /**
     * Get the value of name_client
     */
    public function getName_client()
    {
        return $this->name_client;
    }

    /**
     * Set the value of name_client
     *
     * @return self
     */
    public function setName_client($name_client)
    {
        $this->name_client = $name_client;

        return $this;
    }

    /**
     * Get the value of note
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set the value of note
     *
     * @return self
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get the value of ordem
     */
    public function getOrdem()
    {
        return $this->ordem;
    }

    /**
     * Set the value of ordem
     *
     * @return self
     */
    public function setOrdem(array $ordem)
    {
        $this->ordem = $ordem;

        return $this;
    }

    /**
     * Get the value of obs_text
     */
    public function getObs_text()
    {
        return $this->obs_text;
    }

    /**
     * Set the value of obs_text
     *
     * @return self
     */
    public function setObs_text($obs_text)
    {
        $this->obs_text = $obs_text;

        return $this;
    }

    /**
     * Get the value of text
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set the value of text
     *
     * @return self
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }


}
