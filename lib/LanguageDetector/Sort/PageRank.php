<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2013 César D. Rodas                                               |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/
namespace LanguageDetector\Sort;

use LanguageDetector\SortInterface;

class PageRank implements SortInterface
{
    protected $damping = 0.85;
    protected $convergence = 0.001;
    protected $outlinks = array();
    protected $graph    = array();
    protected $nodes    = array();

    // addNode {{{
    protected function addNode($source, $dest)
    {
        if ($source === $dest) {
            return false;
        }
        if (empty($this->outlinks[$source])) {
            $this->outlinks[$source] = 0;
        }
        if (empty($this->graph[$dest])) {
            $this->graph[$dest] = array();
        }

        $this->graph[$dest][] = $source;
        $this->outlinks[$source]++;

        $this->nodes[$source] = 0.15;
        $this->nodes[$dest]   = 0.15;

        return true;
    }
    // }}}

    // subs(array $a, array $b) {{{
    /**
     *  Array substraction
     *
     *  @param array $a
     *  @param array $b
     *  
     *  @return array
     */
    final protected function subs($a, $b)
    {
        $array = array();
        if (count($a) != count($a)) {
            throw new \Exception("Array shape mismatch");
        }
        foreach ($a as $index => $value) {
            if (!isset($b[$index])) {
                throw new \Exception("Array shape mismatch");
            }
            $array[$index] = $value - $b[$index]; 
        }
        return $array;
    }
    // }}}

    // mult(array $a, array $b) {{{
    /**
     *  Array multiplication
     *
     *  @param array $a
     *  @param array $b
     *  
     *  @return array
     */
    final protected function mult($a, $b)
    {
        $val = 0;
        if (count($a) != count($a)) {
            throw new Exception("Array shape  mismatch");
        }
        foreach ($a as $index => $value) {
            if (!isset($b[$index])) {
                throw new Exception("Array shape  mismatch");
            }
            $val += $b[$index]  * $value;
        }
        return $val;
    }
    // }}}

    // hasCoverge {{{
    protected function hasConverge(Array $newValues)
    {
        $total = count($newValues);
        $diff  = $this->subs($newValues, $this->nodes);
        $done  = (sqrt($this->mult($diff, $diff))/$total) < $this->convergence;
        $this->nodes = $newValues;

        return $done;
    }
    // }}}

    public function sort(Array $ngrams)
    {
        $this->outlinks = array();
        $this->graph    = array();
        $this->nodes    = array();
        $total = count($ngrams);
        for($i=0; $i < $total; $i++) {
            for ($e=$i; $e < $total && $e <= $i+5; $e++) {
                $this->addNode($ngrams[$e], $ngrams[$i]);
                $this->addNode($ngrams[$i], $ngrams[$e]);
            }
        }

        $newvals = $this->nodes;
        do {
            $values  = $this->nodes;
            foreach ($this->graph as $id => $inlinks) {
                $pr = 0;
                foreach ($inlinks as $zid) {
                    $pr += $values[$zid] / $this->outlinks[$zid];
                }
                $pr = (1-$this->damping) * $this->damping * $pr;
                $newvals[$id] = $pr;
            }
        } while(!$this->hasConverge($newvals));

        arsort($newvals);

        return $newvals;
    }
}
