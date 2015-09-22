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

namespace abexto\tests\xdc\orm\phpunits\query\tools;

/**
 * Description of ModifyOrderByWalkerTest
 *
 * @author Andreas Prucha, Abexto - Helicon Software Development
 */
class ModifyOrderByWalkerTest extends \abexto\tests\xdc\orm\common\BaseDoctrinePhpUnit
{
    protected function parseAndApplyModifyOrderByWalker(\Doctrine\ORM\Query $query)
    {
        $parser = new \Doctrine\ORM\Query\Parser($query);
        $AST = $parser->QueryLanguage ();
        $walker = new \abexto\xdc\orm\query\tools\ModifyOrderByWalker($query, null, []);
        $walker->walkSelectStatement($AST);
        return $AST;
    }
    
    public function testClearByEmptyString()
    {
        $query = $this->getEm()->createQuery('Select d from abexto\\tests\\xdc\\orm\\models\\XdcDummy d order by d.id');
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, ['\\abexto\\xdc\\orm\\query\\tools\\ModifyOrderByWalker']);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY, '');
        $AST = $this->parseAndApplyModifyOrderByWalker($query);
        $this->assertNull($AST->orderByClause);
    }
    
    public function testClearByEmptyArray()
    {
        $query = $this->getEm()->createQuery('Select d from abexto\\tests\\xdc\\orm\\models\\XdcDummy d order by d.id');
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, ['\\abexto\\xdc\\orm\\query\\tools\\ModifyOrderByWalker']);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY, '');
        $AST = $this->parseAndApplyModifyOrderByWalker($query);
        $this->assertNull($AST->orderByClause);
    }
    
    public function testDoNothingWithFalse()
    {
        $query = $this->getEm()->createQuery('Select d from abexto\\tests\\xdc\\orm\\models\\XdcDummy d order by d.id');
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, ['\\abexto\\xdc\\orm\\query\\tools\\ModifyOrderByWalker']);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY, false);
        $AST = $this->parseAndApplyModifyOrderByWalker($query);
        $this->assertInstanceOf('\\Doctrine\\ORM\\Query\\AST\\OrderByClause', $AST->orderByClause);
        $this->assertCount(1, $AST->orderByClause->orderByItems);
        $this->assertEquals('id', $AST->orderByClause->orderByItems[0]->expression->field);
    }
    
    public function testReplaceOrderByWithString()
    {
        $query = $this->getEm()->createQuery('Select d from abexto\\tests\\xdc\\orm\\models\\XdcDummy d order by d.id');
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, ['\\abexto\\xdc\\orm\\query\\tools\\ModifyOrderByWalker']);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY, 'd.field1');
        $AST = $this->parseAndApplyModifyOrderByWalker($query);
        $this->assertInstanceOf('\\Doctrine\\ORM\\Query\\AST\\OrderByClause', $AST->orderByClause);
        $this->assertCount(1, $AST->orderByClause->orderByItems);
        $this->assertEquals('field1', $AST->orderByClause->orderByItems[0]->expression->field);
    }
    
    public function testPrependOrderByWithString()
    {
        $query = $this->getEm()->createQuery('Select d from abexto\\tests\\xdc\\orm\\models\\XdcDummy d order by d.id');
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, ['\\abexto\\xdc\\orm\\query\\tools\\ModifyOrderByWalker']);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY, 'd.field1');
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY_MERGE, \abexto\xdc\orm\query\tools\ModifyOrderByWalker::MERGE_PREPEND);
        $AST = $this->parseAndApplyModifyOrderByWalker($query);
        $this->assertInstanceOf('\\Doctrine\\ORM\\Query\\AST\\OrderByClause', $AST->orderByClause);
        $this->assertCount(2, $AST->orderByClause->orderByItems);
        $this->assertEquals('field1', $AST->orderByClause->orderByItems[0]->expression->field);
    }
    
    public function testAppendOrderByWithString()
    {
        $query = $this->getEm()->createQuery('Select d from abexto\\tests\\xdc\\orm\\models\\XdcDummy d order by d.id');
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, ['\\abexto\\xdc\\orm\\query\\tools\\ModifyOrderByWalker']);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY_MERGE, \abexto\xdc\orm\query\tools\ModifyOrderByWalker::MERGE_APPEND);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY, 'd.field1');
        $AST = $this->parseAndApplyModifyOrderByWalker($query);
        $this->assertInstanceOf('\\Doctrine\\ORM\\Query\\AST\\OrderByClause', $AST->orderByClause);
        $this->assertCount(2, $AST->orderByClause->orderByItems);
        $this->assertEquals('field1', $AST->orderByClause->orderByItems[1]->expression->field);
    }
    
    public function testReplaceOrderByWithArray()
    {
        $query = $this->getEm()->createQuery('Select d from abexto\\tests\\xdc\\orm\\models\\XdcDummy d order by d.id');
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, ['\\abexto\\xdc\\orm\\query\\tools\\ModifyOrderByWalker']);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY, ['d.field1' => SORT_ASC]);
        $AST = $this->parseAndApplyModifyOrderByWalker($query);
        $this->assertInstanceOf('\\Doctrine\\ORM\\Query\\AST\\OrderByClause', $AST->orderByClause);
        $this->assertCount(1, $AST->orderByClause->orderByItems);
        $this->assertEquals('field1', $AST->orderByClause->orderByItems[0]->expression->field);
    }
    
    public function testPrependOrderByWithArray()
    {
        $query = $this->getEm()->createQuery('Select d from abexto\\tests\\xdc\\orm\\models\\XdcDummy d order by d.id');
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, ['\\abexto\\xdc\\orm\\query\\tools\\ModifyOrderByWalker']);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY, ['d.field1' => SORT_ASC]);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY_MERGE, \abexto\xdc\orm\query\tools\ModifyOrderByWalker::MERGE_PREPEND);
        $AST = $this->parseAndApplyModifyOrderByWalker($query);
        $this->assertInstanceOf('\\Doctrine\\ORM\\Query\\AST\\OrderByClause', $AST->orderByClause);
        $this->assertCount(2, $AST->orderByClause->orderByItems);
        $this->assertEquals('field1', $AST->orderByClause->orderByItems[0]->expression->field);
    }
    
    public function testAppendOrderByWithArray()
    {
        $query = $this->getEm()->createQuery('Select d from abexto\\tests\\xdc\\orm\\models\\XdcDummy d order by d.id');
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, ['\\abexto\\xdc\\orm\\query\\tools\\ModifyOrderByWalker']);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY_MERGE, \abexto\xdc\orm\query\tools\ModifyOrderByWalker::MERGE_APPEND);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY, 'd.field1');
        $AST = $this->parseAndApplyModifyOrderByWalker($query);
        $this->assertInstanceOf('\\Doctrine\\ORM\\Query\\AST\\OrderByClause', $AST->orderByClause);
        $this->assertCount(2, $AST->orderByClause->orderByItems);
        $this->assertEquals('field1', $AST->orderByClause->orderByItems[1]->expression->field);
    }
    
    public function testComplexOrderBy()
    {
        $query = $this->getEm()->createQuery('Select d from abexto\\tests\\xdc\\orm\\models\\XdcDummy d order by d.id');
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, ['\\abexto\\xdc\\orm\\query\\tools\\ModifyOrderByWalker']);
        $query->setHint(\abexto\xdc\orm\query\tools\ModifyOrderByWalker::HINT_ORDER_BY, 'lower(d.field1) DESC, upper(d.field2) ASC, d.field3 DESC');
        $AST = $this->parseAndApplyModifyOrderByWalker($query);
        $this->assertInstanceOf('\\Doctrine\\ORM\\Query\\AST\\OrderByClause', $AST->orderByClause);
        $this->assertCount(3, $AST->orderByClause->orderByItems);
        $this->assertEquals(true, $AST->orderByClause->orderByItems[0]->isDesc());
        $this->assertEquals(true, $AST->orderByClause->orderByItems[1]->isAsc());
        $this->assertInstanceOf('\\Doctrine\\ORM\Query\\AST\\Functions\\UpperFunction', $AST->orderByClause->orderByItems[1]->expression);
    }
    
    
    
}

