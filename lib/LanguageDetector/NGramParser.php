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
namespace LanguageDetector;

class NGramParser
{
    protected $min;
    protected $max;
    protected $mb;

    public function __construct($min=2, $max=4, $mb = true)
    {
        $this->min = $min;
        $this->max = $max;
        $this->mb  = $mb;
    }

    public function get($text, $limit = -1)
    {
        $strtolower = $this->mb ? 'mb_strtolower' : 'strtolower';
        $strlen     = $this->mb ? 'mb_strlen' : 'strlen';
        $substr     = $this->mb ? 'mb_substr' : 'substr'; 

        $text   = preg_replace('/[ \t\r\n]+/', ' ', $strtolower($text));
        if ($limit > 0) {
            $text = $substr($text, 0, $limit);
        }
        $len    = $strlen($text);
        $min    = $this->min;
        $max    = $this->max;
        $ngrams = array();  
        for ($i=$min; $i <= $max; $i++) {
            for($e=0; $e < $len; $e++) {
                $ngrams[] = $substr($text, $e, $i);
            }
        }

        return $ngrams;
    }

    public function outOfPlace($ngrams, $total, $sample)
    {
        $score = 0;
        $penalty = count($sample)+1;
        foreach (array_slice($ngrams, 0, $this->maxNGrams) as $ngram => $pos) {
            if (empty($sample[$ngram])) {
                $score += $penalty;
                continue;
            } 
            $score += abs($pos - $sample[$ngram]['place']);
        }


        return $score;
    }
    
    public function classify($text, $raw = false)
    {
        if (empty($this->knowledge)) {
            throw new \RuntimeException("Cannot read the knowledge");
        }
        $text   = substr(preg_replace('/[ \t\r\n]+/', ' ', $text), 0, 200);
        $ngrams = self::Get($text);
        $total  = min($this->maxNGrams, count($ngrams));
        $result = array();
        if (empty($ngrams)) {
            return "";
        }
        foreach ($this->knowledge as $lang => $sample) {
            $result[$lang] = 1 - ($this->outOfPlace($ngrams, $total, $sample) / (count($sample) * $total));
        }

        arsort($result);

        if ($raw === true) {
            return $result;
        }

        foreach ($result as $lang => $percentage) {
            if ($percentage >= 0.03) {
                return $lang;
            }
        }

        return array_map(function($v) {
            return array('percentage' => $v, 'trusted' => false);
        }, $result);
    }

    public function addSample($label, $sample)
    {
        if (empty($this->samples[$label])) {
            $this->samples[$label] = array();
        }
        $this->samples[$label][] = $sample;

        return $this;
    }

    public function learn()
    {
        if (empty($this->samples)) {
            throw new \RuntimeException("Missing samples");
        }
       
        foreach ($this->samples as $lang => $samples) {
            $ngrams = array();
            $tmp    = array_slice(self::Get(implode(" ", $samples)), 0, $this->maxNGrams);
            $total  = array_sum($tmp);
            $i      = 0;
            foreach($tmp as $ngram => $count) {
                $ngrams[$ngram] = array('place' => $i++, 'total' => $count, 'percentage' => $count / $total);
            }
            $this->knowledge[$lang] = $ngrams;
        }

        file_put_contents($this->file, '<?php return ' . var_export($this->knowledge, true) . ';', LOCK_EX);

        return true;
    }
}
