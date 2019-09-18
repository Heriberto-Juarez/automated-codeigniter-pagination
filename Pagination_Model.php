<?php
defined('BASEPATH') OR exit('No direct script access allowed');

final class Pagination_Model extends CI_Model
{
    private $consulta;
    private $campos;
    private $pagina_actual;
    private $total_resultados;
    private $resultados_por_pagina;
    private $offset;
    private $tabla;
    private $total_paginas;
    public $config;

    public function __construct()
    {

        parent::__construct();
        $this->resultados_por_pagina = 8;
        $this->campos = "*";
        $this->offset = 0;
        $this->config = ["base_url" => base_url() . 'pagina/', "paginas_mostradas" => 16];
        $this->pagina_actual = 1;
    }

    /**
     * @return mixed
     */
    public function getConsulta()
    {
        return $this->consulta;
    }

    /**
     * @param mixed $consulta
     */
    public function setConsulta($consulta): void
    {
        $this->consulta = $consulta;
    }

    /**
     * @return string
     */
    public function getCampos(): string
    {
        return $this->campos;
    }

    /**
     * @param string $campos
     */
    public function setCampos(string $campos): void
    {
        $this->campos = $campos;
    }

    /**
     * @return mixed
     */
    public function getPaginaActual()
    {
        return $this->pagina_actual;
    }

    /**
     * @param mixed $pagina_actual
     */
    public function setPaginaActual($pagina_actual): void
    {
        $this->pagina_actual = intval($pagina_actual);
        $this->offset = ($this->pagina_actual - 1) * $this->resultados_por_pagina;
        if ($this->offset < 0) {
            $this->offset = 0;
        }
    }

    /**
     * @return mixed
     */
    public function getTotalResultados()
    {
        return $this->total_resultados;
    }

    /**
     * @param mixed $total_resultados
     */
    public function setTotalResultados($total_resultados): void
    {
        $this->total_resultados = $total_resultados;
    }

    /**
     * @return int
     */
    public function getResultadosPorPagina(): int
    {
        return $this->resultados_por_pagina;
    }

    /**
     * @param int $resultados_por_pagina
     */
    public function setResultadosPorPagina(int $resultados_por_pagina): void
    {
        $this->resultados_por_pagina = $resultados_por_pagina;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * @return mixed
     */
    public function getTabla()
    {
        return $this->tabla;
    }

    /**
     * @param mixed $tabla
     */
    public function setTabla($tabla): void
    {
        $this->tabla = $tabla;
    }

    /**
     * @return mixed
     */
    public function getTotalPaginas()
    {
        return $this->total_paginas;
    }

    /**
     * @param mixed $total_paginas
     */
    public function setTotalPaginas($total_paginas): void
    {
        $this->total_paginas = $total_paginas;
    }

    /*
     * Obtiene la página actual a través de la URL
     * */
    public function pagina_actual_uri()
    {
        /*
         * Utilizar uri_to_assoc() en lugar de ruri_to_assoc() cuando no se este utilizando la funcionalidad de cambio de rutas de CodeIgniter
         * */
        $uri_assoc = $this->uri->ruri_to_assoc();
        /*
         * Si en el arreglo que devuelve el método ruri_to_assoc() no se encuentra la clave página
         * se tomará la página 1 por defecto
         * */
        if (array_key_exists("pagina", $uri_assoc)) {
            $p = $uri_assoc['pagina']; //p será la pagina encontrada en la url
            /*
             * Si la página de la url es un valor númerico se tomara como página actual, de lo contrario la página por defecto tomada será la 1
             * */
            if (preg_match("/^[0-9]+$/", $p)) {
                $this->setPaginaActual($p);
            } else {
                $this->setPaginaActual(1);
            }
        } else {
            $this->setPaginaActual(1);
        }
    }

    public function select($array = [])
    {
        $this->db->select($this->campos)->from($this->tabla);
        if (array_key_exists("where", $array)) {
            foreach ($array['where'] as $cond) {
                $this->db->where($cond['key'], $cond['value']);
            }
        }
        if (array_key_exists("join", $array)) {
            foreach ($array["join"] as $j) {
                if (!array_key_exists("type", $j)) $j['type'] = 'inner';
                $this->db->join($j['table'], $j['cond'], $j['type']);
            }
        }
        if(array_key_exists("order_by", $array) && array_key_exists("field",$array['order_by'])){
            if(!array_key_exists("direction", $array['order_by'])) $array['order_by']['direction'] = 'desc';
            $this->db->order_by($array['order_by']['field'], $array['order_by']['direction']);
        }
    }

    public function count($array = [])
    {
        $this->select($array);
        return $this->db->get();
    }

    public function compute($array = [])
    {
        $this->total_resultados = $this->count($array)->num_rows();
        $this->total_paginas = ceil($this->total_resultados / $this->resultados_por_pagina);
        $this->offset = ($this->pagina_actual - 1) * $this->resultados_por_pagina;
        $this->select($array);
        return $this->db->limit($this->resultados_por_pagina)->offset($this->offset)->get()->result_array();
    }

    public function paginas()
    {

        $max_paginas_por_lado = ceil(($this->config['paginas_mostradas'] - 2) / 2);
        $str = '';
        if ($this->total_paginas < 2) {
            return '';
        }
        $str .= '<nav aria-label="Page navigation example" class="d-flex">
            <ul class="pagination mx-auto">';
        if ($this->pagina_actual > 1) {
            $str .= '<li class="page-item"><a class="page-link" href="' . $this->config['base_url'] . ($this->pagina_actual - 1) . '"> < </a></li>';
        }
        if ($this->pagina_actual - $max_paginas_por_lado > 1) {
            $str .= '<li class="page-item"><a class="page-link bg-info text-white" href="' . $this->config['base_url'] . '1">1</a></li>';
        }
        for ($i = ($this->pagina_actual - $max_paginas_por_lado); $i < $this->pagina_actual; $i++) {
            if ($i > 0) {
                $str .= '<li class="page-item ' . ($this->pagina_actual == $i ? 'active' : "") . '"><a class="page-link" href="' . $this->config['base_url'] . $i . '">' . $i . '</a>
                        </li>';
            }
        }
        $paginas_mostradas = 0;
        for ($i = $this->pagina_actual; $i <= $this->total_paginas; $i++) {
            if ($paginas_mostradas < $max_paginas_por_lado) {
                $str .= '<li class="page-item ' . ($this->pagina_actual == $i ? 'active' : '') . '"><a class="page-link"
                                                                                             href="' . $this->config['base_url'] . $i . '">' . $i . '</a></li>';
                $paginas_mostradas++;
            }
        }
        if (($this->total_paginas - $this->pagina_actual - $max_paginas_por_lado) >= 0) {
            $str .= '<li class="page-item"><a class="page-link bg-info text-white"
                                             href="' . $this->config['base_url'] . $this->total_paginas . '">' . $this->total_paginas . '</a></li>';
        }
        if ($this->pagina_actual < ($this->total_paginas)) {
            $str .= '
                    <li class="page-item"><a class="page-link" href="' . $this->config['base_url'] . ($this->pagina_actual + 1) . '"> > </a></li>';
        }
        $str .= '</ul></nav>';
        return $str;
    }
}
