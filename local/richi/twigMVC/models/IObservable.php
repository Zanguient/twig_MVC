<?php
interface IObservable {
    
    function registerObserver(IObserver $view);
    
    function removeObserver(IObserver $view);
    
    function notifyObservers();
 
}
