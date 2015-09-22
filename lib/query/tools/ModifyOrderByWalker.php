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

namespace abexto\xdc\orm\query\tools;

/**
 * Modifies the ordering of an DQL Select statment
 *
 * @author Andreas Prucha, Abexto - Helicon Software Development
 */
class ModifyOrderByWalker extends \Doctrine\ORM\Query\TreeWalkerAdapter
{

    const MERGE_REPLACE = 0;
    const MERGE_PREPEND = -1;
    const MERGE_APPEND = +1;
    const HINT_ORDER_BY = 'abexto.ydoctrine.ModifyOrderByWalker.OrderBy';
    const HINT_ORDER_BY_MERGE = 'abexto.ydoctrine.ModifyOrderByWalker.OrderByMerge';

    /**
     * Parses the order by clause and transforms it into AST
     * @param string $orderByClause
     * @return \Doctrine\ORM\Query\AST\OrderByClause
     */
    protected function parseOrderByClause($orderByClause)
    {
        if ($orderByClause === '') {
            return new \Doctrine\ORM\Query\AST\OrderByClause([]); // Empty order by ===> RETURN
        }
        
        // We use the doctrine DQL parser here. 
        // Fortunately the function OrderByClause is public, thus we can call it directly.
        // Let's hope that it stays public and never breaks
        $dummyQuery = $this->_getQuery()->getEntityManager()->createQuery('order by ' . $orderByClause);
        $parser = new \Doctrine\ORM\Query\Parser($dummyQuery);
        $parser->getLexer()->moveNext(); // Move to first token
        return $parser->OrderByClause();
    }

    /**
     * Creates an OrderByClause from array
     * 
     * @param array $orderByDefinition
     */
    protected function createOrderByClause(array $orderByDefinition)
    {
        $orderByItems = [];
        foreach ($orderByDefinition as $fn => $fd) {
            $fnp = explode('.', $fn);
            if (count($fnp) > 1) {
                $expr = new \Doctrine\ORM\Query\AST\PathExpression(\Doctrine\ORM\Query\AST\PathExpression::TYPE_STATE_FIELD, $fnp[0], $fnp[1]);
                $expr->type = \Doctrine\ORM\Query\AST\PathExpression::TYPE_STATE_FIELD;
            } else {
                $expr = $fnp[0];
            }
            $orderByItem = new \Doctrine\ORM\Query\AST\OrderByItem($expr);
            switch ($fd) {
                case SORT_ASC:
                    $orderByItem->type = 'ASC';
                    break;
                case SORT_DESC:
                    $orderByItem->type = 'DESC';
                    break;
                default:
                    $orderByItem->type = $fd;
            }
            $orderByItems[] = $orderByItem;
        }
        return new \Doctrine\ORM\Query\AST\OrderByClause($orderByItems);
    }

    /**
     * Merges the new order by clause to the existing clause
     * 
     * @param \Doctrine\ORM\Query\AST\SelectStatement $AST
     * @param \Doctrine\ORM\Query\AST\OrderByClause $newOrderByClause
     */
    protected function mergeOrderByItems($AST, 
            \Doctrine\ORM\Query\AST\OrderByClause $newOrderByClause = null, 
            $mergeMode = self::MERGE_REPLACE)
    {
        $mergeMode = (int)$mergeMode; // If FALSE, NULL or something like that we asume MERGE_REPLACE

        // Check if a previous order by clause exists. If not, we have nothing to do and
        // can just return the new order by clause
        if (!$AST->orderByClause instanceof \Doctrine\ORM\Query\AST\OrderByClause) {
            $AST->orderByClause = new \Doctrine\ORM\Query\AST\OrderByClause([]);
        }

        // Merge order by
        switch ($mergeMode) {
            case self::MERGE_REPLACE:
                $AST->orderByClause->orderByItems = $newOrderByClause->orderByItems;
                break;
            case self::MERGE_APPEND:
                $AST->orderByClause->orderByItems = array_merge($AST->orderByClause->orderByItems, $newOrderByClause->orderByItems);
                break;
            case self::MERGE_PREPEND:
                $AST->orderByClause->orderByItems = array_merge($newOrderByClause->orderByItems, $AST->orderByClause->orderByItems);
                break;
            default:
                throw new Exception('Unknown merge mode for '.___CLASS__.'::'.__METHOD__);
        }

        // Set to null if empty
        if (empty($AST->orderByClause->orderByItems)) {
            $AST->orderByClause = null;
        }
    }

    /**
     * Trasforms the order by hint
     */
    protected function getOrderBy()
    {
        $ob = $this->_getQuery()->getHint(self::HINT_ORDER_BY);

        // Check if we have a valid declaration
        if ($ob === null || $ob === false) {
            return null; // No valid declaration ===> RETURN
        }
        
        // Transform declaration in to OrderByClause objects
        if (is_string($ob)) {
            $result = $this->parseOrderByClause($ob);
        } else {
            $result = $this->createOrderByClause($ob);
        }
        
        return $result;
    }

    /**
     * {@inheritDoc}
     * 
     * @param SelectStatement $AST
     * @return void     * 
     */
    public function walkSelectStatement(\Doctrine\ORM\Query\AST\SelectStatement $AST)
    {
        $query = $this->_getQuery();

        $hints = $query->getHints();

        parent::walkSelectStatement($AST);

        $newOrderBy = $this->getOrderBy($AST->orderByClause);
        
        if ($newOrderBy !== null) {
            $this->mergeOrderByItems($AST, $newOrderBy, $query->getHint(self::HINT_ORDER_BY_MERGE));
        }
    }

}
