<?php

/*
 * Copyright (c) 2015, Andreas Prucha, Abexto - Helicon Software Development
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 * 
 * *  Redistributions of source code must retain the above copyright notice, this 
 *    list of conditions and the following disclaimer.
 * *  Redistributions in binary form must reproduce the above copyright notice, 
 *    this list of conditions and the following disclaimer in the documentation 
 *    and/or other materials provided with the distribution.
 * *  Neither the name of Abexto, Helicon Software Development, Andreas Prucha
 *    nor the names of its contributors may be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
 * IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, 
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF 
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace abexto\xdc\orm\query;

/**
 * Provides caching for direct access to Doctrine Query Parser
 *
 * @author Andreas Prucha, Abexto - Helicon Software Development
 */
class CachedParserHelper
{
    /**
     * @var \Doctrine\ORM\AbstractQuery
     */
    protected $query = null;
    
    private $_parserResult = null;
    
    public function __construct(\Doctrine\ORM\AbstractQuery $query)
    {
        $this->query = $query;
    }
    
    protected function getCacheId()
    {
        $hints = $this->query->getHints();
        ksort($hints);
        
        $types = array();
        foreach ($this->query->getParameters() as $parameter) {
            $types[$parameter->getName()] = $parameter->getType();
        }

        $platform = $this->query->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform()
            ->getName();
        
        return md5(serialize([
            'dql' => $this->query->getDQL(),
            'platform' => $platform,
            'filters' => ($this->query->getEntityManager()->hasFilters()) ? $this->query->getEntityManager()->getFilters()->getHash() : '',
            'firstResult' => $this->query->getFirstResult(),
            'maxResult' => $this->query->getMaxResults(),
            'hydrationMode' => $this->query->getHydrationMode(),
            'types' => $types,
            'hints' => $hints,
            'salt' => __CLASS__.'V1'
        ]));
    }
    
    protected function doParseWithoutCache()
    {
        $parser = new \Doctrine\ORM\Query\Parser($this->query);
        return $parser->parse();
    }
    
    protected function doParse()
    {
        $result = null;
        $hash   = $this->getCacheId();
        
        $usedCacheDriver = (!$this->query->getExpireQueryCache()) ? $this->query->getQueryCacheDriver() : null;

        if ($usedCacheDriver) {
            $result = $usedCacheDriver->fetch($hash);
        }
        
        if (!$result instanceof \Doctrine\ORM\Query\ParserResult) {
            $result = $this->doParseWithoutCache();
            if ($usedCacheDriver) {
                $usedCacheDriver->save($hash, $result, $this->query->getQueryCacheLifetime());
            }
        }
        
        return $result;
    }
    
    public function getParserResult()
    {
        if (!$this->_parserResult instanceof \Doctrine\ORM\Query\ParserResult) {
            $this->_parserResult = $this->doParse();
        }
        return $this->_parserResult;
    }
    
}
