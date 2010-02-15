<?php

/**
 * PluginDmChessKing
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6820 2009-11-30 17:27:49Z jwage $
 */
abstract class PluginDmChessKing extends BaseDmChessKing
{

  protected function getBasicTargetSquares()
  {
    $mySquare = $this->getSquare();

    $squares = array(
      $mySquare->getSquareByRelativePos(0, -1),
      $mySquare->getSquareByRelativePos(0, 1),
      $mySquare->getSquareByRelativePos(-1, -1),
      $mySquare->getSquareByRelativePos(-1, 0),
      $mySquare->getSquareByRelativePos(-1, 1),
      $mySquare->getSquareByRelativePos(1, -1),
      $mySquare->getSquareByRelativePos(1, 0),
      $mySquare->getSquareByRelativePos(1, 1)
    );

    // castles
    if (!$this->hasMoved() && !$this->isAttacked())
    {
      $opponent = $this->get('Player')->getOpponent();
      
      foreach($this->getPieceFilter()->filterAlive($this->getPieceFilter()->filterType($this->get('Player')->get('Pieces'), 'rook')) as $rook)
      {
        if (!$rook->hasMoved())
        {
          $canCastle = true;
          $squaresToRook = $this->getSquaresToRook($rook);
          foreach($squaresToRook as $square)
          {
            if (!$square->isEmpty() || $square->isControlledBy($opponent))
            {
              $canCastle = false;
              break;
            }
          }
          if ($canCastle)
          {
            $squares[] = $squaresToRook[1];
          }
        }
      }
    }

    return $this->cannibalismFilter($squares);
  }
  
  public function isAttacked()
  {
    if($this->hasCache('is_attacked'))
    {
      return $this->getCache('is_attacked');
    }
    
    return $this->setCache('is_attacked', $this->getGame()->get('is_started') && $this->getSquare()->isControlledBy($this->get('Player')->getOpponent()));
  }

  public function canCastleQueenside()
  {
    return $this->canCastle(-4);
  }
  
  public function canCastleKingside()
  {
    return $this->canCastle(3);
  }

  protected function canCastle($relativeX)
  {
    return
    !$this->hasMoved() &&
    ($rook = $this->getSquare()->getSquareByRelativePos($relativeX, 0)->getPiece()) &&
    ($rook->isType('rook') && !$rook->hasMoved());
  }

  protected function getSquaresToRook(DmChessRook $rook)
  {
    $squares = array();
    $rookSquare = $rook->getSquare();
    $kingSquare = $this->getSquare();
    
    $dx = $kingSquare->getX() > $rookSquare->getX() ? -1 : 1;
    
    $square = $kingSquare;
    while(($square = $square->getSquareByRelativePos($dx, 0)) && !$square->is($rookSquare))
    {
      $squares[] = $square;
    }
    
    return $squares;
  }
  
  // handle castle
  public function postMove(dmChessSquare $from, dmChessSquare $to)
  {
    if (2 == abs($from->getX() - $to->getX()))
    {
      if ($to->getX() == 7)
      {
        $side = 'king';
        $rookSquare = $to->getSquareByRelativePos(1, 0);
        $newRookSquare = $to->getSquareByRelativePos(-1, 0);
      }
      else
      {
        $side = 'queen';
        $rookSquare = $to->getSquareByRelativePos(-2, 0);
        $newRookSquare = $to->getSquareByRelativePos(1, 0);
      }
      
      $rook = $rookSquare->getPiece();
      $rook->x = $newRookSquare->getX();
      
      $rook->save();
      
      $this->getEventDispatcher()->notify(new dmChessPieceCastleEvent($this, 'dm.chess.piece_castle', array(
        'side'        => $side,
        'king'        => $this,
        'king_from'   => $from,
        'king_to'     => $to,
        'rook'        => $rook,
        'rook_from'   => $rookSquare,
        'rook_to'     => $newRookSquare
      )));
    }
  }
  
  public function preInsert($event)
  {
    parent::preInsert($event);
    
    if(!$this->y)
    {
      $this->y = $this->Player->isWhite() ? 1 : 8;
    }
  }

}