<?php

namespace LanguageDetector\Sort;

class Bayes extends Sum
{
    public function summarize(Array $knowledge, $max)
    {
        $tngrams = array();
        foreach ($knowledge as $group => $ngrams) {
            foreach ($ngrams as $ngram => $info) {
                if (empty($ngrams[$ngram])) {
                    $tngrams[$ngram] = array();
                }
                $tngrams[$ngram][$group] = $info;
            }
        }

        $groups = array();
        foreach ($tngrams as $ngram => $ngrams) {
            $total = array_sum($ngrams);
            foreach ($ngrams as $group => $count) {
                if (empty($groups[$group])) {
                    $groups[$group] = array();
                }
                $groups[$group][$ngram] = $count / $total;
            }
        }

        return array_map(function($values) use ($max) {
            arsort($values);
            return $values;
        }, $groups);
    }
}

