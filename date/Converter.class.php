<?php

class date_Converter
{   
    protected static function getOffset($timespan)
    {
        $old = date_default_timezone_get();
        date_default_timezone_set(RequestContext::getInstance()->getTimeZone());
        $offset = date('Z', $timespan);
        date_default_timezone_set($old);
        return intval($offset);       
    }
      
    /**
     * @param Long $timeSpanDate
     * @return Long
     */
    protected static function convertTimeSpanDateToGMT($timeSpanDate)
    {
       return $timeSpanDate - self::getOffset($timeSpanDate); 
    }
       
     /**
     * @param Long $timeSpanDate
     * @return Long
     */   
    protected static function convertTimeSpanDateToLocal($timeSpanDate)
    {
       return $timeSpanDate + self::getOffset($timeSpanDate); 
    }
    
    /**
     * @param String $stringDate
     * @return String
     */
    protected static function convertStringDateToGMT($stringDate)
    {
         return date('Y-m-d H:i:s', self::convertTimeSpanDateToGMT(strtotime($stringDate)));
    }
    
    /**
     * @param String $stringDate
     * @return String
     */
    protected static function convertStringDateToLocal($stringDate)
    {
         return date('Y-m-d H:i:s', self::convertTimeSpanDateToLocal(strtotime($stringDate)));
    }      
    
    /**
     * @param date_Calendar $calendarDate
     * @return date_Calendar
     */
    protected static function convertCalendarDateToGMT($calendarDate)
    {
         return date_Calendar::getInstance(self::convertStringDateToGMT($calendarDate->toString()));
    }
    
    /**
     * @param date_Calendar $calendarDate
     */
    protected static function convertCalendarDateToLocal($calendarDate)
    {
         return date_Calendar::getInstance(self::convertStringDateToLocal($calendarDate->toString()));
    }

    /**
     * @param Long|String|date_Calendar $date
     * @return Long|String|date_Calendar
     */
    public static function convertDateToGMT($date)
    {
        if (empty($date))
        {
            return $date;
        } 

        if (is_long($date))
        {
            return self::convertTimeSpanDateToGMT($date);
        }
        
        if (is_string($date))
        {
             return self::convertStringDateToGMT($date);
        }
        
        if ($date instanceof date_Calendar)
        {
            return self::convertCalendarDateToGMT($date);
        }

        return $date;
    }

    /**
     * @param Long|String|date_Calendar $date
     * @return Long|String|date_Calendar
     */
    public static function convertDateToLocal($date)
    {
        if (empty($date))
        {
            return $date;
        } 

        if (is_long($date))
        {
            return self::convertTimeSpanDateToLocal($date);
        }
        
        if (is_string($date))
        {
             return self::convertStringDateToLocal($date);
        }
        
        if ($date instanceof date_Calendar)
        {
            return self::convertCalendarDateToLocal($date);
        }

        return $date;
    }
}
