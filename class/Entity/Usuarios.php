<?php

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "usuarios")]
class Usuarios {

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    protected $id;
    
    #[ORM\Column(type: "string")]
    protected $id_telegram;

    public function getId ()
    {
        return $this->id;
    }

    public function setId ($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getId_telegram ()
    {
        return $this->id_telegram;
    }
    
    public function setId_telegram ($id_telegram)
    {
        $this->id_telegram = $id_telegram;

        return $this;
    }
}