<?php

namespace Controller;

class Homepage extends Base
{
    public function get()
    {
        header('Location: /file-manager');
    }
}
