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

class Learn
{
    protected $samples = array();
    protected $callback;
    protected $config;
    protected $output = array();

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function addStepcallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \RuntimeException("\$callback must be callable");
        }
        $this->callback = $callback;
        return $this;
    }

    public function addSample($label, $text)
    {
        if (empty($this->samples[$label])) {
            $this->samples[$label] = array();
        }
        $this->samples[$label][] = $text;
    }


    public function save($output)
    {
        if (empty($this->output)) {
            $this->doLearn();
        }

        $format = new Format($output);
        $format->dump($this->output);

        return $this;
    }

    public function doLearn()
    {
        if (empty($this->samples)) {
            throw new \Exception("You need to provide samples");
        }

        $sort     = $this->config->getSortObject();
        $max      = $this->config->maxNGram();
        $parser   = $this->config->getParser();
        $callback = $this->callback;
        $tokens   = array();
        foreach ($this->samples as $lang => $texts) {
            if ($callback) {
                $callback($lang, 'start');
            }
            $text   = implode("\n", $texts);
            $ngrams =  $sort->sort($parser->get($text));
            foreach (array_slice($ngrams, 0, $max) as $ngram => $score) {
                $tokens[$ngram] = isset($tokens[$ngram]) ? $tokens[$ngram]+1 : 1;
            }
            $knowledge[$lang] = $ngrams;
        }

        $threshold = count($this->samples) * .8;
        $blacklist = array_filter(array_map(function($count) use ($threshold) {
            return $count >= $threshold;
        }, $tokens));

        $langs = array();
        foreach ($knowledge as $lang => $ngrams) {
            $pos  = 0;
            $data = array();
            foreach ($ngrams as $ngram => $score) {
                if (!empty($blacklist[$ngram])) {
                    continue;
                }
                $data[$ngram] = array('pos' => $pos++, 'score' => $score);
                if ($pos === $max) {
                    break;
                }
            }

            $langs[$lang] = $data;
        }

        $this->output = array(
            'config' => $this->config,
            'blacklist' => $blacklist,
            'tokens' => $tokens,
            'data'   => $langs,
        );

        return $this;
    }
}
