<?php

namespace ITE\DoctrineBundle\Query\Mssql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Class Field
 * @package ITE\DoctrineBundle\Query\Mssql
 */
class Field extends FunctionNode
{
    private $field = null;
    private $values = array();
    
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
		
        // Do the field.
        $this->field = $parser->ArithmeticPrimary();
		
        // Add the strings to the values array. FIELD must
        // be used with at least 1 string not including the field.
		
        $lexer = $parser->getLexer();
		
        while (count($this->values) < 1 || 
            $lexer->lookahead['type'] != Lexer::T_CLOSE_PARENTHESIS) {
            $parser->match(Lexer::T_COMMA);
            $this->values[] = $parser->ArithmeticPrimary();
        }
		
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
	
    public function getSql(SqlWalker $sqlWalker)
    {
        $query = 'CASE ' . $this->field->dispatch($sqlWalker) . ' ';
		
        for ($i = 0; $i < count($this->values); $i++) {
            if ($i > 0) {
                $query .= ' ';
            }
			
            $query .= 'WHEN ' . $this->values[$i]->dispatch($sqlWalker) . ' THEN ' . $i;
        }

        $query .= ' END';
		
        return $query;
    }
}