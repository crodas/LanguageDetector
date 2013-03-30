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

class Detect
{
    protected $config;
    protected $data;
    protected $parser;
    protected $sort;

    public function __construct($datafile)
    {
        $format = new Format($datafile);
        $data   = $format->load();
        foreach (array('config', 'data') as $type) {
            if (empty($data[$type])) {
                throw new \Exception("Invalid data file, missing {$type}");
            }
            $this->$type = $data[$type];
        }
        $this->parser   = $this->config->getParser();
        $this->sort     = $this->config->getSortObject();
        $this->distance = $this->config->GetDistanceObject();
    }

    public function detect($text, $limit = 200)
    {
        $ngrams = $this->sort->sort($this->parser->get($text, $limit));
        $total  = min($this->config->maxNGram(), count($ngrams));
        foreach ($this->data as $lang => $data) {
            $distance[] = array(
                'lang'  => $lang, 
                'score' => 1-($this->distance->distance($data, $ngrams) / (count($data) * $total)),
            );
        }

        usort($distance, function($a, $b) {
            return $a['score'] > $b['score'] ? -1 : 1; 
        });

        if ($distance[0]['score'] - $distance[1]['score'] <= 0.02) {
            /** First and second language candidates are similar, strip the first 200
              letters and re-run the test */
            return $this->detect(substr($text, 200), $limit);
        }

        return $distance[0]['lang'];
    }

}
