<?php

namespace think\api\request;

use think\api\Request;

/**
 * @method $this withCity($value)
 */
class AirSearch extends Request
{
}

/**
 * @method $this withCity($value)
 */
class AirPm extends Request
{
}

class AirCity extends Request
{
}

class AirPmCity extends Request
{
    public $uri = 'air/pm_city';
}

/**
 * @method $this withDate($value)
 */
class AlmanacDate extends Request
{
}

/**
 * @method $this withDate($value)
 */
class AlmanacHour extends Request
{
}

/**
 * @method $this withQq($value)
 */
class AlmanacQq extends Request
{
}

/**
 * @method $this withArea($value)
 */
class AqiSearch extends Request
{
}

/**
 * @method $this withKeyword($value)
 */
class BaiduIndex extends Request
{
}

/**
 * @method $this withDomainName($value)
 * @method $this withKeyword($value)
 */
class BaiduPcRank extends Request
{
    public $uri = 'baidu/pc_rank';
}

/**
 * @method $this withDomainName($value)
 * @method $this withKeyword($value)
 */
class BaiduMobileRank extends Request
{
    public $uri = 'baidu/mobile_rank';
}

/**
 * @method $this withDomainName($value)
 */
class BaiduLinks extends Request
{
}

/**
 * @method $this withDomainName($value)
 */
class BaiduPages extends Request
{
}

/**
 * @method $this withUrl($value)
 */
class BaiduPcUrlPages extends Request
{
    public $uri = 'baidu/pc_url_pages';
}

/**
 * @method $this withUrl($value)
 */
class BaiduMobileUrlPages extends Request
{
    public $uri = 'baidu/mobile_url_pages';
}

/**
 * @method $this withDomainName($value)
 */
class BaiduPcWeight extends Request
{
    public $uri = 'baidu/pc_weight';
}

/**
 * @method $this withDomainName($value)
 */
class BaiduMobileWeight extends Request
{
    public $uri = 'baidu/mobile_weight';
}

/**
 * @method $this withKeyword($value)
 */
class BaiduKeyword extends Request
{
}

/**
 * @method $this withName($value)
 * @method $this withCardNo($value)
 */
class BankcardTwoAuth extends Request
{
    public $uri = 'bankcard/two_auth';
}

/**
 * @method $this withName($value)
 * @method $this withIdNum($value)
 * @method $this withCardNo($value)
 */
class BankcardThreeAuth extends Request
{
    public $uri = 'bankcard/three_auth';
}

/**
 * @method $this withName($value)
 * @method $this withIdNum($value)
 * @method $this withCardNo($value)
 */
class BankcardThreeAuthDetail extends Request
{
    public $uri = 'bankcard/three_auth_detail';
}

/**
 * @method $this withName($value)
 * @method $this withIdNum($value)
 * @method $this withCardNo($value)
 * @method $this withMobile($value)
 */
class BankcardAuth extends Request
{
}

/**
 * @method $this withName($value)
 * @method $this withIdNum($value)
 * @method $this withCardNo($value)
 * @method $this withMobile($value)
 */
class BankcardAuthDetail extends Request
{
    public $uri = 'bankcard/auth_detail';
}

/**
 * @method $this withParam($value)
 */
class BankcardAuthSecret extends Request
{
    public $uri = 'bankcard/auth_secret';
}

/**
 * @method $this withCode($value)
 */
class BarcodeQuery extends Request
{
}

/**
 * @method $this withHeight($value)
 * @method $this withWeight($value)
 * @method $this withSex($value)
 */
class BmiIndex extends Request
{
}

class BookCatalog extends Request
{
}

/**
 * @method $this withCatalogId($value)
 * @method $this withPn($value)
 * @method $this withRn($value)
 */
class BookQuery extends Request
{
}

/**
 * @method $this withSub($value)
 */
class BookIsbn extends Request
{
}

/**
 * @method $this withNum($value)
 */
class BrainTeaserIndex extends Request
{
    public $uri = 'brain_teaser/index';
}

/**
 * @method $this withDate($value)
 */
class CalendarDay extends Request
{
}

/**
 * @method $this withYearMonth($value)
 */
class CalendarMonth extends Request
{
}

/**
 * @method $this withYear($value)
 */
class CalendarYear extends Request
{
}

/**
 * @method $this withImgBase64($value)
 * @method $this withTypeId($value)
 * @method $this withConvertToJpg($value)
 * @method $this withNeedMorePrecise($value)
 */
class CaptchaNumber extends Request
{
}

/**
 * @method $this withImgBase64($value)
 * @method $this withTypeId($value)
 * @method $this withConvertToJpg($value)
 */
class CaptchaChinese extends Request
{
}

/**
 * @method $this withImgBase64($value)
 */
class CaptchaAlgorism extends Request
{
}

/**
 * @method $this withFirstLetter($value)
 */
class CarBrand extends Request
{
}

/**
 * @method $this withBrandid($value)
 * @method $this withLevelid($value)
 */
class CarSeries extends Request
{
}

/**
 * @method $this withSeriesId($value)
 * @method $this withYear($value)
 */
class CarModels extends Request
{
}

/**
 * @method $this withCode($value)
 */
class CarObd extends Request
{
}

/**
 * @method $this withCity($value)
 * @method $this withKeywords($value)
 * @method $this withPage($value)
 * @method $this withFormat($value)
 */
class CarRegion extends Request
{
}

/**
 * @method $this withLon($value)
 * @method $this withLat($value)
 * @method $this withPage($value)
 * @method $this withFormat($value)
 * @method $this withR($value)
 */
class CarNearby extends Request
{
}

/**
 * @method $this withCarNumber($value)
 * @method $this withCarType($value)
 */
class CarQuery extends Request
{
}

/**
 * @method $this withDayNum($value)
 * @method $this withCity($value)
 */
class CarLimit extends Request
{
}

/**
 * @method $this withVin($value)
 */
class CarVin extends Request
{
}

/**
 * @method $this withVin($value)
 */
class CarVinPro extends Request
{
    public $uri = 'car/vin_pro';
}

/**
 * @method $this withText($value)
 * @method $this withType($value)
 */
class CharConvert extends Request
{
}

/**
 * @method $this withWord($value)
 */
class ChengyuQuery extends Request
{
}

/**
 * @method $this withWord($value)
 */
class ChengyuAllusion extends Request
{
}

/**
 * @method $this withWord($value)
 * @method $this withUserid($value)
 * @method $this withStatetime($value)
 */
class ChengyuJielong extends Request
{
}

class ChengyuGuess extends Request
{
}

/**
 * @method $this withConsName($value)
 * @method $this withType($value)
 */
class ConstellationQuery extends Request
{
}

/**
 * @method $this withMen($value)
 * @method $this withWomen($value)
 */
class ConstellationMatch extends Request
{
}

/**
 * @method $this withMen($value)
 * @method $this withWomen($value)
 */
class ConstellationZodiac extends Request
{
}

class DreamCategory extends Request
{
}

/**
 * @method $this withQ($value)
 * @method $this withCid($value)
 * @method $this withFull($value)
 */
class DreamQuery extends Request
{
}

/**
 * @method $this withId($value)
 */
class DreamId extends Request
{
}

/**
 * @method $this withSubject($value)
 * @method $this withModel($value)
 * @method $this withTestType($value)
 */
class DrivingQuery extends Request
{
}

class DrivingAnswer extends Request
{
}

/**
 * @method $this withRand($value)
 * @method $this withDate($value)
 */
class EnglishDay extends Request
{
}

/**
 * @method $this withKeyword($value)
 */
class EnterpriseDetailInfo extends Request
{
    public $uri = 'enterprise/detail_info';
}

/**
 * @method $this withKeyword($value)
 * @method $this withName($value)
 * @method $this withOperName($value)
 */
class EnterpriseVerify extends Request
{
}

/**
 * @method $this withKeyword($value)
 * @method $this withPageIndex($value)
 * @method $this withPageSize($value)
 */
class EnterpriseCopyright extends Request
{
}

/**
 * @method $this withKeyword($value)
 */
class EnterpriseCreditcode extends Request
{
}

/**
 * @method $this withKeyword($value)
 * @method $this withPageSize($value)
 * @method $this withPageNo($value)
 * @method $this withSearchType($value)
 * @method $this withIntCls($value)
 */
class EnterpriseTrademark extends Request
{
}

/**
 * @method $this withDs($value)
 * @method $this withQ($value)
 * @method $this withP($value)
 * @method $this withPs($value)
 * @method $this withS($value)
 * @method $this withHl($value)
 */
class EnterprisePatent extends Request
{
}

class ExchangeQuery extends Request
{
}

class ExchangeCurrency extends Request
{
}

/**
 * @method $this withFrom($value)
 * @method $this withTo($value)
 */
class ExchangeConvert extends Request
{
}

/**
 * @method $this withBank($value)
 */
class ExchangePrice extends Request
{
}

class ExchangeFrate extends Request
{
}

/**
 * @method $this withCom($value)
 * @method $this withNu($value)
 * @method $this withPhone($value)
 */
class ExpIndex extends Request
{
}

/**
 * @method $this withCom($value)
 * @method $this withNu($value)
 * @method $this withPhone($value)
 */
class ExpressQuery extends Request
{
}

/**
 * @method $this withCom($value)
 * @method $this withNu($value)
 * @method $this withCallBackUrl($value)
 * @method $this withOutCode($value)
 * @method $this withPhone($value)
 */
class ExpressAsyc extends Request
{
}

/**
 * @method $this withNu($value)
 */
class ExpressCompany extends Request
{
}

/**
 * @method $this withExpName($value)
 * @method $this withMaxSize($value)
 * @method $this withPage($value)
 */
class ExpressExpList extends Request
{
    public $uri = 'express/exp_list';
}

/**
 * @method $this withSiteName($value)
 * @method $this withAddr($value)
 * @method $this withContactInfo($value)
 */
class ExpressDot extends Request
{
}

/**
 * @method $this withText($value)
 */
class ExpressAddress extends Request
{
}

/**
 * @method $this withText($value)
 * @method $this withTo($value)
 */
class FanyiIndex extends Request
{
}

/**
 * @method $this withOrgCity($value)
 * @method $this withDstCity($value)
 * @method $this withFlightNo($value)
 */
class FlightQuery extends Request
{
}

/**
 * @method $this withFlightNo($value)
 * @method $this withFlightDate($value)
 */
class FlightHistory extends Request
{
}

/**
 * @method $this withFlightNo($value)
 * @method $this withFlightDate($value)
 */
class FlightFuture extends Request
{
}

/**
 * @method $this withWord($value)
 * @method $this withMode($value)
 * @method $this withNum($value)
 * @method $this withPage($value)
 */
class FoodNutrient extends Request
{
}

/**
 * @method $this withWord($value)
 * @method $this withNum($value)
 * @method $this withPage($value)
 */
class FoodMenu extends Request
{
}

/**
 * @method $this withLng($value)
 * @method $this withLat($value)
 * @method $this withType($value)
 */
class GeoIndex extends Request
{
}

/**
 * @method $this withLng($value)
 * @method $this withLat($value)
 * @method $this withType($value)
 */
class GeoConvert extends Request
{
}

class GoldQuery extends Request
{
}

class GoldFuture extends Request
{
}

class GoldBank extends Request
{
}

/**
 * @method $this withDirector($value)
 */
class GstoreMovieByDirector extends Request
{
    public $uri = 'gstore/movie_by_director';
}

/**
 * @method $this withActor1($value)
 * @method $this withActor2($value)
 */
class GstoreMovieByActors extends Request
{
    public $uri = 'gstore/movie_by_actors';
}

/**
 * @method $this withDisease($value)
 */
class GstoreSymptom extends Request
{
}

/**
 * @method $this withSymptom($value)
 */
class GstoreDisease extends Request
{
}

/**
 * @method $this withDisease($value)
 */
class GstoreTabooFood extends Request
{
    public $uri = 'gstore/taboo_food';
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 */
class HotWeixin extends Request
{
}

class HotDouyin extends Request
{
}

class HotVideo extends Request
{
}

class HotWeibo extends Request
{
}

/**
 * @method $this withIdcard($value)
 * @method $this withRealname($value)
 * @method $this withOrderid($value)
 */
class IdcardQuery extends Request
{
}

/**
 * @method $this withCardno($value)
 */
class IdcardIndex extends Request
{
    public $method = 'GET';
}

/**
 * @method $this withName($value)
 * @method $this withIdNum($value)
 */
class IdcardAuth extends Request
{
}

/**
 * @method $this withImage($value)
 * @method $this withPolitician($value)
 * @method $this withAntiporn($value)
 * @method $this withTerror($value)
 * @method $this withAntiSpam($value)
 * @method $this withDisgust($value)
 * @method $this withWatermark($value)
 * @method $this withQuality($value)
 */
class ImageImgCensor extends Request
{
    public $uri = 'image/img_censor';
}

/**
 * @method $this withImage($value)
 */
class ImageGifDetect extends Request
{
    public $uri = 'image/gif_detect';
}

/**
 * @method $this withIp($value)
 */
class IpIndex extends Request
{
    public $method = 'GET';
}

/**
 * @method $this withId($value)
 */
class JdDetail extends Request
{
}

/**
 * @method $this withSort($value)
 * @method $this withPage($value)
 * @method $this withPagesize($value)
 * @method $this withTime($value)
 */
class JokeQuery extends Request
{
}

/**
 * @method $this withPage($value)
 * @method $this withPagesize($value)
 */
class JokeLatest extends Request
{
}

class JokeRand extends Request
{
}

class LifeTip extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class LiteraryPoetry extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class LiteraryTang extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class LiterarySong extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class LiteraryYuan extends Request
{
}

class LiteraryQuote extends Request
{
}

class LiteraryMemo extends Request
{
}

class LiteraryQuan extends Request
{
}

/**
 * @method $this withRand($value)
 * @method $this withDate($value)
 */
class LiteraryOne extends Request
{
}

class LotteryTypes extends Request
{
}

/**
 * @method $this withLotteryId($value)
 * @method $this withLotteryNo($value)
 */
class LotteryQuery extends Request
{
}

/**
 * @method $this withLotteryId($value)
 * @method $this withLotteryNo($value)
 * @method $this withLotteryRes($value)
 */
class LotteryBonus extends Request
{
}

/**
 * @method $this withLotteryId($value)
 * @method $this withPage($value)
 * @method $this withPageSize($value)
 */
class LotteryHistory extends Request
{
}

/**
 * @method $this withCarNumber($value)
 * @method $this withCarCode($value)
 * @method $this withCarEngineCode($value)
 * @method $this withJgjId($value)
 * @method $this withCarType($value)
 */
class LuozQuery extends Request
{
}

/**
 * @method $this withPreCarNum($value)
 * @method $this withProvince($value)
 */
class LuozSupport extends Request
{
}

/**
 * @method $this withCarNumber($value)
 * @method $this withCarCode($value)
 * @method $this withCarEngineCode($value)
 */
class LuozTimes extends Request
{
}

/**
 * @method $this withCarNumber($value)
 * @method $this withCarCode($value)
 * @method $this withCarEngineCode($value)
 * @method $this withCarType($value)
 */
class LuozNewEnergy extends Request
{
    public $uri = 'luoz/new_energy';
}

/**
 * @method $this withJszh($value)
 * @method $this withDabh($value)
 */
class LuozPoints extends Request
{
}

/**
 * @method $this withCarNo($value)
 * @method $this withVin($value)
 * @method $this withEngineNo($value)
 * @method $this withType($value)
 */
class LuozHistory extends Request
{
}

/**
 * @method $this withPrefix($value)
 */
class LuozRule extends Request
{
}

/**
 * @method $this withName($value)
 * @method $this withCardNo($value)
 * @method $this withArchviesNo($value)
 */
class LuozLicense extends Request
{
}

/**
 * @method $this withHphm($value)
 * @method $this withEngineno($value)
 * @method $this withClassno($value)
 * @method $this withHpzl($value)
 */
class LuozQuery2 extends Request
{
}

/**
 * @method $this withAbbr($value)
 */
class LuozCitylist extends Request
{
}

/**
 * @method $this withLat($value)
 * @method $this withLon($value)
 * @method $this withPage($value)
 * @method $this withPagesize($value)
 * @method $this withR($value)
 */
class LuozNearby extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class MedicineIndex extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class MedicineSearch extends Request
{
}

/**
 * @method $this withMoney($value)
 * @method $this withType($value)
 */
class MoneyConvert extends Request
{
}

/**
 * @method $this withTitle($value)
 * @method $this withSmode($value)
 * @method $this withPagesize($value)
 * @method $this withOffset($value)
 */
class MovieSearch extends Request
{
}

/**
 * @method $this withLat($value)
 * @method $this withLon($value)
 * @method $this withRadius($value)
 */
class MovieCinemas extends Request
{
}

/**
 * @method $this withCityid($value)
 * @method $this withKeyword($value)
 * @method $this withPage($value)
 * @method $this withPagesize($value)
 */
class MovieCinemaSearch extends Request
{
    public $uri = 'movie/cinema_search';
}

/**
 * @method $this withCinemaid($value)
 * @method $this withMovieid($value)
 */
class MovieCinemaMovies extends Request
{
    public $uri = 'movie/cinema_movies';
}

/**
 * @method $this withCityid($value)
 */
class MovieToday extends Request
{
}

class MovieSupportCity extends Request
{
    public $uri = 'movie/support_city';
}

/**
 * @method $this withCityid($value)
 * @method $this withMovieid($value)
 */
class MovieShowCinema extends Request
{
    public $uri = 'movie/show_cinema';
}

/**
 * @method $this withMovieid($value)
 */
class MovieIndex extends Request
{
}

/**
 * @method $this withType($value)
 */
class NewsToutiao extends Request
{
}

class NewsHot extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsWoman extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsRubbish extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsEnvironmental extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsMovie extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsDigiccy extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsHouse extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsBlockchain extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsSicprobe extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withSource($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsGeneral extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withSrc($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsTop extends Request
{
}

/**
 * @method $this withAreaname($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsArea extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsAuto extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsInternet extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsAgriculture extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsHanfu extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsComic extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsFinance extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsCba extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsAi extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsIt extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsVr extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsBeauty extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsQiwen extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsHealth extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsTravel extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsMobile extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsMilitary extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsApple extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsStartup extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsKeji extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsFootball extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsNba extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsSport extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsFun extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsWorld extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsInternal extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 */
class NewsSocial extends Request
{
}

/**
 * @method $this withImage($value)
 * @method $this withSide($value)
 */
class OcrIdcard extends Request
{
}

/**
 * @method $this withImage($value)
 * @method $this withImageType($value)
 * @method $this withOcrType($value)
 */
class OcrIdOcr extends Request
{
    public $uri = 'ocr/id_ocr';
}

/**
 * @method $this withImage($value)
 * @method $this withImageType($value)
 */
class OcrBankcard extends Request
{
}

/**
 * @method $this withImage($value)
 * @method $this withImageType($value)
 */
class OcrBusinessLicense extends Request
{
    public $uri = 'ocr/business_license';
}

/**
 * @method $this withImage($value)
 * @method $this withImageType($value)
 */
class OcrDrivingLicense extends Request
{
    public $uri = 'ocr/driving_license';
}

/**
 * @method $this withImage($value)
 * @method $this withImageType($value)
 */
class OcrVehicleLicense extends Request
{
    public $uri = 'ocr/vehicle_license';
}

/**
 * @method $this withImage($value)
 * @method $this withImageType($value)
 */
class OcrPassport extends Request
{
}

/**
 * @method $this withImage($value)
 * @method $this withImageType($value)
 */
class OcrInvoice extends Request
{
}

/**
 * @method $this withImage($value)
 * @method $this withImageType($value)
 */
class OcrHand extends Request
{
}

/**
 * @method $this withImage($value)
 * @method $this withImageType($value)
 * @method $this withOcrType($value)
 * @method $this withDetectRisk($value)
 */
class OcrIdCardText extends Request
{
    public $uri = 'ocr/id_card_text';
}

/**
 * @method $this withImage($value)
 */
class OcrFace extends Request
{
}

/**
 * @method $this withImgurl($value)
 */
class OcrTxt extends Request
{
}

/**
 * @method $this withImage($value)
 */
class OcrArithmetic extends Request
{
}

/**
 * @method $this withImageBase64($value)
 */
class OcrEdu extends Request
{
}

/**
 * @method $this withName($value)
 * @method $this withIdentityCard($value)
 * @method $this withFacePic($value)
 * @method $this withIdcardFront($value)
 * @method $this withIdcardBackground($value)
 */
class OcrRealPerson extends Request
{
    public $uri = 'ocr/real_person';
}

/**
 * @method $this withImage($value)
 * @method $this withImageUrl($value)
 */
class OcrMaskDetect extends Request
{
    public $uri = 'ocr/mask_detect';
}

class OilQuery extends Request
{
}

/**
 * @method $this withCityName($value)
 * @method $this withCurrentPage($value)
 * @method $this withPageSize($value)
 */
class ParkQuery extends Request
{
}

/**
 * @method $this withLongitude($value)
 * @method $this withLatitude($value)
 * @method $this withDistance($value)
 * @method $this withCurrentPage($value)
 * @method $this withPageSize($value)
 */
class ParkNearby extends Request
{
}

/**
 * @method $this withParkId($value)
 * @method $this withParkUUId($value)
 */
class ParkInfo extends Request
{
}

class ParkCityList extends Request
{
    public $uri = 'park/city_list';
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 * @method $this withType($value)
 */
class PetIndex extends Request
{
}

/**
 * @method $this withPostcode($value)
 * @method $this withPage($value)
 * @method $this withPagesize($value)
 */
class PostcodeQuery extends Request
{
}

/**
 * @method $this withPid($value)
 * @method $this withCid($value)
 * @method $this withDid($value)
 * @method $this withQ($value)
 * @method $this withPage($value)
 * @method $this withPagesize($value)
 */
class PostcodeSearch extends Request
{
}

class PostcodePcd extends Request
{
}

/**
 * @method $this withFid($value)
 */
class PostcodeZone extends Request
{
}

/**
 * @method $this withText($value)
 * @method $this withEl($value)
 * @method $this withBgcolor($value)
 * @method $this withFgcolor($value)
 * @method $this withLogo($value)
 * @method $this withW($value)
 * @method $this withM($value)
 * @method $this withLw($value)
 * @method $this withType($value)
 */
class QrcodeIndex extends Request
{
}

/**
 * @method $this withQrpic($value)
 * @method $this withQrurl($value)
 */
class QrcodeCodec extends Request
{
}

/**
 * @method $this withWord($value)
 * @method $this withNum($value)
 * @method $this withPage($value)
 */
class RumourIndex extends Request
{
}

class SecondhandCarBrand extends Request
{
    public $uri = 'secondhand_car/brand';
}

/**
 * @method $this withBrandId($value)
 */
class SecondhandCarFamily extends Request
{
    public $uri = 'secondhand_car/family';
}

/**
 * @method $this withFamilyId($value)
 */
class SecondhandCarModel extends Request
{
    public $uri = 'secondhand_car/model';
}

class SecondhandCarProvince extends Request
{
    public $uri = 'secondhand_car/province';
}

/**
 * @method $this withProvinceId($value)
 */
class SecondhandCarCity extends Request
{
    public $uri = 'secondhand_car/city';
}

/**
 * @method $this withRegDate($value)
 * @method $this withCity($value)
 * @method $this withProvinceId($value)
 * @method $this withAutoHomeId($value)
 * @method $this withMiles($value)
 */
class SecondhandCarQuery extends Request
{
    public $uri = 'secondhand_car/query';
}

/**
 * @method $this withSignId($value)
 * @method $this withTemplateId($value)
 * @method $this withPhone($value)
 * @method $this withParams($value)
 */
class SmsSend extends Request
{
}

/**
 * @method $this withImage($value)
 */
class ShopSnap extends Request
{
}

/**
 * @method $this withName($value)
 * @method $this withSex($value)
 * @method $this withNation($value)
 * @method $this withBirth($value)
 * @method $this withConstellation($value)
 * @method $this withPage($value)
 * @method $this withNum($value)
 */
class StarIndex extends Request
{
}

/**
 * @method $this withXing($value)
 */
class SurnameIndex extends Request
{
}

class SpringTravelCitys extends Request
{
}

/**
 * @method $this withFrom($value)
 * @method $this withTo($value)
 */
class SpringTravelQuery extends Request
{
}

/**
 * @method $this withCityId($value)
 */
class SpringTravelHsjg extends Request
{
}

class SpringTravelRisk extends Request
{
}

/**
 * @method $this withId($value)
 */
class TaobaoDetail extends Request
{
}

/**
 * @method $this withId($value)
 * @method $this withInfo($value)
 * @method $this withAreaId($value)
 */
class TaobaoItem extends Request
{
}

/**
 * @method $this withId($value)
 */
class TaobaoInfo extends Request
{
}

/**
 * @method $this withId($value)
 */
class TaobaoImage extends Request
{
}

/**
 * @method $this withItemId($value)
 */
class TaobaoShop extends Request
{
}

/**
 * @method $this withItemId($value)
 */
class TaobaoAlibabaInfo extends Request
{
    public $uri = 'taobao/alibaba_info';
}

/**
 * @method $this withTkl($value)
 * @method $this withDepth($value)
 */
class TaobaokeQuery extends Request
{
}

/**
 * @method $this withOrderNo($value)
 */
class TaobaokeCheckOrder extends Request
{
    public $uri = 'taobaoke/check_order';
}

/**
 * @method $this withRealname($value)
 * @method $this withIdcard($value)
 * @method $this withMobile($value)
 * @method $this withType($value)
 * @method $this withShowid($value)
 * @method $this withProvince($value)
 * @method $this withDetail($value)
 */
class TelecomQuery extends Request
{
}

/**
 * @method $this withRealname($value)
 * @method $this withIdcard($value)
 * @method $this withMobile($value)
 */
class TelecomDetail extends Request
{
}

/**
 * @method $this withPhone($value)
 */
class TelecomLocation extends Request
{
}

/**
 * @method $this withTel($value)
 */
class TelecomIdentify extends Request
{
}

/**
 * @method $this withChars($value)
 */
class TelecomCodes extends Request
{
}

/**
 * @method $this withWord($value)
 */
class TimeLunar extends Request
{
}

/**
 * @method $this withDate($value)
 * @method $this withMode($value)
 */
class TimeHoliday extends Request
{
}

/**
 * @method $this withCity($value)
 */
class TimeWorld extends Request
{
}

/**
 * @method $this withDate($value)
 */
class TodayEvent extends Request
{
}

/**
 * @method $this withEId($value)
 */
class TodayDetail extends Request
{
}

/**
 * @method $this withWord($value)
 * @method $this withMode($value)
 * @method $this withNum($value)
 * @method $this withPage($value)
 */
class TrashIndex extends Request
{
}

/**
 * @method $this withSpeech($value)
 * @method $this withFormat($value)
 * @method $this withType($value)
 */
class TrashVoice extends Request
{
}

/**
 * @method $this withImage($value)
 * @method $this withType($value)
 */
class TrashImage extends Request
{
}

/**
 * @method $this withQ($value)
 * @method $this withType($value)
 */
class TrashSearch extends Request
{
}

/**
 * @method $this withQuestion($value)
 * @method $this withUser($value)
 */
class TulingIndex extends Request
{
}

/**
 * @method $this withMobiles($value)
 * @method $this withType($value)
 */
class UnnBatchUcheck extends Request
{
    public $uri = 'unn/batch_ucheck';
}

/**
 * @method $this withMobile($value)
 * @method $this withOrderNo($value)
 */
class UnnStatus extends Request
{
}

/**
 * @method $this withRealname($value)
 * @method $this withIdcard($value)
 * @method $this withBankcard($value)
 * @method $this withUorderid($value)
 * @method $this withIsshow($value)
 */
class Verifybankcard3Query extends Request
{
}

/**
 * @method $this withRealname($value)
 * @method $this withIdcard($value)
 * @method $this withBankcard($value)
 * @method $this withMobile($value)
 * @method $this withUorderid($value)
 * @method $this withIsshow($value)
 */
class Verifybankcard4Query extends Request
{
}

/**
 * @method $this withFrom($value)
 * @method $this withLng($value)
 * @method $this withLat($value)
 * @method $this withNeedMoreDay($value)
 * @method $this withNeedIndex($value)
 * @method $this withNeedHourData($value)
 * @method $this withNeed3HourForcast($value)
 * @method $this withNeedAlarm($value)
 */
class WeatherCoords extends Request
{
}

/**
 * @method $this withIp($value)
 * @method $this withNeedMoreDay($value)
 * @method $this withNeedIndex($value)
 * @method $this withNeedHourData($value)
 * @method $this withNeed3HourForcast($value)
 * @method $this withNeedAlarm($value)
 */
class WeatherIp extends Request
{
}

/**
 * @method $this withArea($value)
 * @method $this withAreaCode($value)
 * @method $this withNeedMoreDay($value)
 * @method $this withNeedIndex($value)
 * @method $this withNeedHourData($value)
 * @method $this withNeed3HourForcast($value)
 * @method $this withNeedAlarm($value)
 */
class WeatherArea extends Request
{
}

/**
 * @method $this withArea($value)
 * @method $this withAreaCode($value)
 */
class WeatherAreaForecast24 extends Request
{
    public $uri = 'weather/area_forecast24';
}

/**
 * @method $this withArea($value)
 * @method $this withAreaCode($value)
 * @method $this withNeed3HourForcast($value)
 */
class WeatherAreaForecast7 extends Request
{
    public $uri = 'weather/area_forecast7';
}

/**
 * @method $this withArea($value)
 * @method $this withAreaCode($value)
 */
class WeatherAreaForecast15 extends Request
{
    public $uri = 'weather/area_forecast15';
}

/**
 * @method $this withArea($value)
 * @method $this withAreaCode($value)
 */
class WeatherAreaForecast40 extends Request
{
    public $uri = 'weather/area_forecast40';
}

/**
 * @method $this withArea($value)
 * @method $this withAreaCode($value)
 * @method $this withMonth($value)
 * @method $this withStartDate($value)
 * @method $this withEndDate($value)
 */
class WeatherAreaHistory extends Request
{
    public $uri = 'weather/area_history';
}

/**
 * @method $this withArea($value)
 * @method $this withSpotId($value)
 * @method $this withNeedMoreDay($value)
 * @method $this withNeedIndex($value)
 * @method $this withNeedHourData($value)
 * @method $this withNeed3HourForcast($value)
 * @method $this withNeedAlarm($value)
 */
class WeatherScenic extends Request
{
}

/**
 * @method $this withPostCode($value)
 * @method $this withPhoneCode($value)
 * @method $this withNeedMoreDay($value)
 * @method $this withNeedIndex($value)
 * @method $this withNeedHourData($value)
 * @method $this withNeed3HourForcast($value)
 * @method $this withNeedAlarm($value)
 */
class WeatherZip extends Request
{
}

/**
 * @method $this withArea($value)
 */
class WeatherAreaId extends Request
{
    public $uri = 'weather/area_id';
}

/**
 * @method $this withCity($value)
 */
class WeatherQuery extends Request
{
}

/**
 * @method $this withCity($value)
 */
class WeatherLife extends Request
{
}

class WeatherWids extends Request
{
}

class WeatherCityList extends Request
{
    public $uri = 'weather/city_list';
}

/**
 * @method $this withDomain($value)
 */
class WebsiteBeian extends Request
{
}

/**
 * @method $this withUrl($value)
 */
class WebsiteQq extends Request
{
}

/**
 * @method $this withUrl($value)
 */
class WebsiteCheatlink extends Request
{
}

/**
 * @method $this withIp($value)
 */
class WebsiteCheatip extends Request
{
}

/**
 * @method $this withContent($value)
 */
class WebsiteAntispam extends Request
{
}

/**
 * @method $this withContent($value)
 */
class WebsiteAdreview extends Request
{
}

/**
 * @method $this withImgurl($value)
 */
class WebsiteImgcensor extends Request
{
}

/**
 * @method $this withLinks($value)
 */
class WebsiteSurl extends Request
{
}

/**
 * @method $this withUrl($value)
 */
class WebsiteShorturl extends Request
{
}

/**
 * @method $this withDomain($value)
 */
class WebsiteHttps extends Request
{
}

/**
 * @method $this withDomain($value)
 */
class WebsiteBaidu extends Request
{
}

/**
 * @method $this withDomain($value)
 */
class WebsiteSogou extends Request
{
}

/**
 * @method $this withDomain($value)
 */
class WebsiteSo extends Request
{
}

/**
 * @method $this withDomain($value)
 */
class WebsiteIpv6 extends Request
{
}

/**
 * @method $this withDomain($value)
 */
class WebsiteDomain extends Request
{
}

/**
 * @method $this withIp($value)
 * @method $this withLongitude($value)
 * @method $this withLatitude($value)
 */
class WebsiteIp extends Request
{
}

/**
 * @method $this withDomain($value)
 */
class WebsiteWabeian extends Request
{
}

/**
 * @method $this withDomainName($value)
 */
class WebsiteTdk extends Request
{
}

/**
 * @method $this withDomainName($value)
 */
class WebsiteIcp extends Request
{
}

/**
 * @method $this withCompanyName($value)
 */
class WebsiteCompany extends Request
{
}

/**
 * @method $this withDomainName($value)
 */
class WebsiteAlexa extends Request
{
}

/**
 * @method $this withDomainName($value)
 */
class WebsiteWhois extends Request
{
}

/**
 * @method $this withQueryData($value)
 * @method $this withQueryType($value)
 */
class WebsiteWhoisReverse extends Request
{
    public $uri = 'website/whois_reverse';
}

/**
 * @method $this withDomainName($value)
 * @method $this withYear($value)
 * @method $this withWeek($value)
 */
class WebsiteTop extends Request
{
}

/**
 * @method $this withUrl($value)
 * @method $this withFormat($value)
 */
class WebsiteHtmlpic extends Request
{
}

/**
 * @method $this withUrl($value)
 */
class WebsiteHtmltext extends Request
{
}

/**
 * @method $this withUrl($value)
 * @method $this withType($value)
 * @method $this withWidth($value)
 */
class WebsiteUrl2pic extends Request
{
}

/**
 * @method $this withWord($value)
 * @method $this withPage($value)
 * @method $this withTypeid($value)
 * @method $this withSrc($value)
 * @method $this withNum($value)
 */
class WechatChoice extends Request
{
}

/**
 * @method $this withWord($value)
 * @method $this withPage($value)
 */
class WechatSearch extends Request
{
}

/**
 * @method $this withUrl($value)
 */
class WechatLink extends Request
{
}

/**
 * @method $this withUrl($value)
 */
class WechatCheck extends Request
{
}

/**
 * @method $this withDomain($value)
 */
class WechatDomainCheck extends Request
{
    public $uri = 'wechat/domain_check';
}

/**
 * @method $this withUrl($value)
 */
class WechatRead extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 * @method $this withTypeid($value)
 */
class WikiIndex extends Request
{
}

class WikiTiku extends Request
{
}

class WikiRiddle extends Request
{
}

class WikiLantern extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withPage($value)
 * @method $this withWord($value)
 * @method $this withType($value)
 */
class WikiStory extends Request
{
}

/**
 * @method $this withNum($value)
 */
class WikiTongue extends Request
{
}

class WikiDoggerel extends Request
{
}

/**
 * @method $this withNum($value)
 */
class WikiXiehou extends Request
{
}

/**
 * @method $this withWord($value)
 */
class WikiHotword extends Request
{
}

/**
 * @method $this withNum($value)
 */
class WikiGodreply extends Request
{
}

/**
 * @method $this withMobile($value)
 * @method $this withIp($value)
 * @method $this withType($value)
 */
class WoolWcheck extends Request
{
}

/**
 * @method $this withMobile($value)
 * @method $this withIp($value)
 * @method $this withType($value)
 */
class WoolWtag extends Request
{
}

/**
 * @method $this withNum($value)
 * @method $this withContent($value)
 */
class WordSegment extends Request
{
}

/**
 * @method $this withWord($value)
 */
class XinhuaQuery extends Request
{
}

class XinhuaBushou extends Request
{
}

class XinhuaPinyin extends Request
{
}

/**
 * @method $this withWord($value)
 * @method $this withPage($value)
 * @method $this withPagesize($value)
 * @method $this withIsjijie($value)
 * @method $this withIsxiangjie($value)
 */
class XinhuaQuerybs extends Request
{
}

/**
 * @method $this withWord($value)
 * @method $this withPage($value)
 * @method $this withPagesize($value)
 * @method $this withIsjijie($value)
 * @method $this withIsxiangjie($value)
 */
class XinhuaQuerypy extends Request
{
}

/**
 * @method $this withWord($value)
 */
class XinhuaQueryid extends Request
{
}

/**
 * @method $this withWord($value)
 * @method $this withType($value)
 */
class XinhuaResemble extends Request
{
}

/**
 * @method $this withText($value)
 */
class XinhuaConvertPy extends Request
{
    public $uri = 'xinhua/convert_py';
}

class XnbIndex extends Request
{
}

/**
 * @method AirSearch airSearch() 城市空气质量指数查询
 * @method AirPm airPm() 城市空气PM2.5查询
 * @method AirCity airCity() 城市空气质量支持城市
 * @method AirPmCity airPmCity() 城市空气PM2.5支持城市
 * @method AlmanacDate almanacDate() 提供老黄历查询,黄历每日吉凶宜忌查询
 * @method AlmanacHour almanacHour() 提供老黄历查询,黄历每日吉凶宜忌查询
 * @method AlmanacQq almanacQq() 根据传入的参数qq号码测试qq的吉凶
 * @method AqiSearch aqiSearch() 空气质量指数查询
 * @method BaiduIndex baiduIndex() 关键词百度指数
 * @method BaiduPcRank baiduPcRank() 关键词百度PC排名
 * @method BaiduMobileRank baiduMobileRank() 关键词百度移动排名
 * @method BaiduLinks baiduLinks() 百度反链数
 * @method BaiduPages baiduPages() 百度收录量
 * @method BaiduPcUrlPages baiduPcUrlPages() 百度PC-URL收录
 * @method BaiduMobileUrlPages baiduMobileUrlPages() 百度mobile-URL收录
 * @method BaiduPcWeight baiduPcWeight() 百度PC权重
 * @method BaiduMobileWeight baiduMobileWeight() 百度移动权重
 * @method BaiduKeyword baiduKeyword() 百度关键词收录量
 * @method BankcardTwoAuth bankcardTwoAuth() 银行卡二要素标准版
 * @method BankcardThreeAuth bankcardThreeAuth() 银行卡三要素标准版
 * @method BankcardThreeAuthDetail bankcardThreeAuthDetail() 银行卡三要素详细版
 * @method BankcardAuth bankcardAuth() 银行卡四要素标准版
 * @method BankcardAuthDetail bankcardAuthDetail() 银行卡四要素详情版
 * @method BankcardAuthSecret bankcardAuthSecret() 银行卡四要素加密版
 * @method BarcodeQuery barcodeQuery()
 * @method BmiIndex bmiIndex() bmi查询
 * @method BookCatalog bookCatalog() 图书分类目录
 * @method BookQuery bookQuery() 查询图书数据
 * @method BookIsbn bookIsbn() 查询图书数据
 * @method BrainTeaserIndex brainTeaserIndex() 脑筋急转弯
 * @method CalendarDay calendarDay()
 * @method CalendarMonth calendarMonth()
 * @method CalendarYear calendarYear()
 * @method CaptchaNumber captchaNumber() 识别数字、英文验证码
 * @method CaptchaChinese captchaChinese() 识别中英文验证码
 * @method CaptchaAlgorism captchaAlgorism() 算式验证码识别
 * @method CarBrand carBrand() 返回车辆品牌所有列表，或更具中文拼音首字母查询品牌列表
 * @method CarSeries carSeries() 根据车辆品牌ID查询旗下车系列表
 * @method CarModels carModels() 根据车系id查询旗下车型列表
 * @method CarObd carObd() 通过OBD故障码查询相关信息
 * @method CarRegion carRegion() 按城市检索加油站
 * @method CarNearby carNearby() 附近加油站
 * @method CarQuery carQuery() 查询车辆详细信息
 * @method CarLimit carLimit() 尾号限行
 * @method CarVin carVin() VIN码查询
 * @method CarVinPro carVinPro() VIN码查询-专业版
 * @method CharConvert charConvert() 转换字符串至简体、繁体、火星文，每次最多支持100个字符
 * @method ChengyuQuery chengyuQuery() 根据成语查询详细信息，如：详解、同义词、反义词、读音等信息
 * @method ChengyuAllusion chengyuAllusion() 成语典故
 * @method ChengyuJielong chengyuJielong() 成语接龙
 * @method ChengyuGuess chengyuGuess() 猜成语
 * @method ConstellationQuery constellationQuery() 十二星座的今日运势
 * @method ConstellationMatch constellationMatch() 查询星座配对
 * @method ConstellationZodiac constellationZodiac() 查询生肖配对
 * @method DreamCategory dreamCategory() 梦境类型
 * @method DreamQuery dreamQuery() 根据梦境中梦到的事物解梦
 * @method DreamId dreamId() 根据ID查询解梦信息
 * @method DrivingQuery drivingQuery() 根据输入参数返回相关题目
 * @method DrivingAnswer drivingAnswer() 返回answer字段对应答案信息
 * @method EnglishDay englishDay() 每日一句
 * @method EnterpriseDetailInfo enterpriseDetailInfo() 企业信息精准查询
 * @method EnterpriseVerify enterpriseVerify() 企业三要素核验
 * @method EnterpriseCopyright enterpriseCopyright() 企业著作权查询
 * @method EnterpriseCreditcode enterpriseCreditcode() 企业开票信息查询
 * @method EnterpriseTrademark enterpriseTrademark() 商标信息查询
 * @method EnterprisePatent enterprisePatent() 专利查询
 * @method ExchangeQuery exchangeQuery() 常用汇率查询
 * @method ExchangeCurrency exchangeCurrency() 货币列表
 * @method ExchangeConvert exchangeConvert() 实时货币汇率查询换算，数据仅供参考，交易时以银行柜台成交价为准
 * @method ExchangePrice exchangePrice() 100外币兑人民币，更新时间2分钟，此汇率仅供参考
 * @method ExchangeFrate exchangeFrate() 此汇率仅供参考，更新时间2分钟，以中国银行各分行实际交易汇率为准
 * @method ExpIndex expIndex()
 * @method ExpressQuery expressQuery() 快递物流跟踪（实时）
 * @method ExpressAsyc expressAsyc() 快递物流跟踪（异步）
 * @method ExpressCompany expressCompany() 快递单号查询快递公司
 * @method ExpressExpList expressExpList() 快递公司列表
 * @method ExpressDot expressDot() 网点查询
 * @method ExpressAddress expressAddress() 收货信息智能解析
 * @method FanyiIndex fanyiIndex() 语言翻译
 * @method FlightQuery flightQuery() 实时起降信息查询
 * @method FlightHistory flightHistory() 历史起降信息查询
 * @method FlightFuture flightFuture() 根据航班号日期查询未来航班信息
 * @method FoodNutrient foodNutrient() 食物营养成分表
 * @method FoodMenu foodMenu() 菜谱查询
 * @method GeoIndex geoIndex() 经纬度地址解析
 * @method GeoConvert geoConvert() 支持百度、谷歌、GPS三大经纬度互相转化
 * @method GoldQuery goldQuery() 上海黄金交易所 2分钟更新一次
 * @method GoldFuture goldFuture() 上海期货交易所 2分钟更新一次
 * @method GoldBank goldBank() 纸黄金
 * @method GstoreMovieByDirector gstoreMovieByDirector() 根据导演查找电影
 * @method GstoreMovieByActors gstoreMovieByActors() 根据演员查找电影
 * @method GstoreSymptom gstoreSymptom() 查询某个疾病的所有症状
 * @method GstoreDisease gstoreDisease() 查询某个症状可能的疾病信息
 * @method GstoreTabooFood gstoreTabooFood() 查询某个疾病不能吃的食物
 * @method HotWeixin hotWeixin() 微信热文榜
 * @method HotDouyin hotDouyin() 抖音热点话题
 * @method HotVideo hotVideo() 抖音热点视频
 * @method HotWeibo hotWeibo() 微博热搜榜
 * @method IdcardQuery idcardQuery()
 * @method IdcardIndex idcardIndex()
 * @method IdcardAuth idcardAuth() 身份证二要素检测
 * @method ImageImgCensor imageImgCensor() 组合服务接口
 * @method ImageGifDetect imageGifDetect() GIF色情图像识别
 * @method IpIndex ipIndex()
 * @method JdDetail jdDetail() 京东商品信息
 * @method JokeQuery jokeQuery() 根据时间戳返回该时间点前或后的笑话列表
 * @method JokeLatest jokeLatest() 获取最新的笑话
 * @method JokeRand jokeRand() 随机获取笑话
 * @method LifeTip lifeTip() 生活小窍门
 * @method LiteraryPoetry literaryPoetry() 唐诗三百首
 * @method LiteraryTang literaryTang() 唐诗大全
 * @method LiterarySong literarySong() 精选宋词
 * @method LiteraryYuan literaryYuan() 元曲三百首
 * @method LiteraryQuote literaryQuote() 古籍名句
 * @method LiteraryMemo literaryMemo() 励志名言
 * @method LiteraryQuan literaryQuan() 朋友圈文案
 * @method LiteraryOne literaryOne() ONE一个
 * @method LotteryTypes lotteryTypes() 获取当前支持的彩种列表
 * @method LotteryQuery lotteryQuery() 根据彩票ID查询开奖结果，数据来源网络，仅供参考。
 * @method LotteryBonus lotteryBonus() 根据投注的彩票号码及期数判断是否中奖，暂只支持双色球、大乐透单注或复式
 * @method LotteryHistory lotteryHistory() 根据彩票ID查询历史开奖结果
 * @method LuozQuery luozQuery() 车辆违章查询
 * @method LuozSupport luozSupport() 用于查询某个地区是否支持违章查询，以及该地区车辆的车架号、发动机号所需的位数。
 * @method LuozTimes luozTimes() 查询车辆违章的次数
 * @method LuozNewEnergy luozNewEnergy() 目前支持广东、上海、江苏、河南、浙江、四川等地区的新能源车牌违章查询
 * @method LuozPoints luozPoints() 累计计分查询
 * @method LuozHistory luozHistory() 查询车辆历史违章
 * @method LuozRule luozRule() 获取城市查询违章的参数规则
 * @method LuozLicense luozLicense() 驾驶证核查
 * @method LuozQuery2 luozQuery2() 违章查询V2
 * @method LuozCitylist luozCitylist() 违章查询支持城市列表
 * @method LuozNearby luozNearby() 附近违章高发地
 * @method MedicineIndex medicineIndex() 中药大全
 * @method MedicineSearch medicineSearch() 药品说明书
 * @method MoneyConvert moneyConvert() 金额转大写
 * @method MovieSearch movieSearch() 关键字检索影片信息
 * @method MovieCinemas movieCinemas() 检索周边影院
 * @method MovieCinemaSearch movieCinemaSearch() 关键字影院检索
 * @method MovieCinemaMovies movieCinemaMovies() 影院上映影片信息
 * @method MovieToday movieToday() 今日上映影片信息
 * @method MovieSupportCity movieSupportCity() 支持城市列表
 * @method MovieShowCinema movieShowCinema() 影片上映影院查询
 * @method MovieIndex movieIndex() 按影片id查询影片信息
 * @method NewsToutiao newsToutiao()
 * @method NewsHot newsHot() 网络热搜排行
 * @method NewsWoman newsWoman() 女性新闻
 * @method NewsRubbish newsRubbish() 垃圾分类新闻资讯接口
 * @method NewsEnvironmental newsEnvironmental() 环保资讯
 * @method NewsMovie newsMovie() 影视资讯
 * @method NewsDigiccy newsDigiccy() 币圈资讯
 * @method NewsHouse newsHouse() 区块链新闻
 * @method NewsBlockchain newsBlockchain() 区块链新闻
 * @method NewsSicprobe newsSicprobe() 科学探索
 * @method NewsGeneral newsGeneral() 综合新闻
 * @method NewsTop newsTop() 今日头条新闻
 * @method NewsArea newsArea() 地区新闻
 * @method NewsAuto newsAuto() 汽车新闻
 * @method NewsInternet newsInternet() 互联网资讯
 * @method NewsAgriculture newsAgriculture() 农业新闻
 * @method NewsHanfu newsHanfu() 汉服新闻
 * @method NewsComic newsComic() 动漫资讯
 * @method NewsFinance newsFinance() 财经新闻
 * @method NewsCba newsCba() CBA新闻
 * @method NewsAi newsAi() 人工智能
 * @method NewsIt newsIt() IT资讯
 * @method NewsVr newsVr() VR科技
 * @method NewsBeauty newsBeauty() 美女图片
 * @method NewsQiwen newsQiwen() 奇闻异事
 * @method NewsHealth newsHealth() 健康知识
 * @method NewsTravel newsTravel() 旅游资讯
 * @method NewsMobile newsMobile() 移动通信
 * @method NewsMilitary newsMilitary() 军事新闻
 * @method NewsApple newsApple() 苹果新闻
 * @method NewsStartup newsStartup() 创业资讯
 * @method NewsKeji newsKeji() 科技新闻
 * @method NewsFootball newsFootball() 足球新闻
 * @method NewsNba newsNba() NBA新闻
 * @method NewsSport newsSport() 体育新闻
 * @method NewsFun newsFun() 娱乐新闻
 * @method NewsWorld newsWorld() 国际新闻
 * @method NewsInternal newsInternal() 国内新闻
 * @method NewsSocial newsSocial() 社会新闻
 * @method OcrIdcard ocrIdcard()
 * @method OcrIdOcr ocrIdOcr() 身份证OCR
 * @method OcrBankcard ocrBankcard() 银行卡OCR
 * @method OcrBusinessLicense ocrBusinessLicense() 营业执照OCR
 * @method OcrDrivingLicense ocrDrivingLicense() 驾驶证
 * @method OcrVehicleLicense ocrVehicleLicense() 行驶证
 * @method OcrPassport ocrPassport() 护照
 * @method OcrInvoice ocrInvoice() 增值税发票
 * @method OcrHand ocrHand() 手写OCR
 * @method OcrIdCardText ocrIdCardText() 身份证文字识别
 * @method OcrFace ocrFace() 人脸识别
 * @method OcrTxt ocrTxt() 通用文字识别
 * @method OcrArithmetic ocrArithmetic() 算式识别
 * @method OcrEdu ocrEdu() 数学试题识别
 * @method OcrRealPerson ocrRealPerson() 实人认证
 * @method OcrMaskDetect ocrMaskDetect() 人脸口罩识别
 * @method OilQuery oilQuery() 今日国内油价查询
 * @method ParkQuery parkQuery() 查询指定城市停车场信息列
 * @method ParkNearby parkNearby() 查询周边停车场信息列表
 * @method ParkInfo parkInfo() 获取停车场详情信息
 * @method ParkCityList parkCityList() 获取开放停车场查询的城市列表
 * @method PetIndex petIndex() 宠物大全
 * @method PostcodeQuery postcodeQuery() 通过邮编查询对应的地名
 * @method PostcodeSearch postcodeSearch() 根据相关条件查询符合条件地区的邮编
 * @method PostcodePcd postcodePcd() 根据相关条件查询符合条件地区的邮编
 * @method PostcodeZone postcodeZone() 全国行政区查询,支持省、市、区（乡镇）、街道。最大4级
 * @method QrcodeIndex qrcodeIndex() 根据传递参数实现二维码生成
 * @method QrcodeCodec qrcodeCodec() 二维码解码
 * @method RumourIndex rumourIndex() 谣言识别
 * @method SecondhandCarBrand secondhandCarBrand() 返回车辆品牌所有列表
 * @method SecondhandCarFamily secondhandCarFamily() 指定品牌全部车系列表
 * @method SecondhandCarModel secondhandCarModel() 指定车系具体车型列表
 * @method SecondhandCarProvince secondhandCarProvince() 估值支持的省份
 * @method SecondhandCarCity secondhandCarCity() 估值支持的城市
 * @method SecondhandCarQuery secondhandCarQuery() 二手车估值
 * @method ShopSnap shopSnap() 拍照购
 * @method SmsSend smsSend() 短信发送
 * @method StarIndex starIndex() 明星百科档案
 * @method SpringTravelQuery springTravelQuery() 疫情政策查询
 * @method SpringTravelCitys springTravelCitys() 疫情政策查询支持城市
 * @method SpringTravelHsjg springTravelHsjg() 城市核酸检测机构
 * @method SpringTravelRisk springTravelRisk() 疫情风险地区查询
 * @method SurnameIndex surnameIndex() 姓氏起源
 * @method TaobaoDetail taobaoDetail() 淘宝商品信息
 * @method TaobaoItem taobaoItem() 淘宝商品信息详情版
 * @method TaobaoInfo taobaoInfo() 淘宝商品信息轻量版
 * @method TaobaoImage taobaoImage() 淘宝商品详情图片
 * @method TaobaoShop taobaoShop() 获取卖家店铺的基本信息
 * @method TaobaoAlibabaInfo taobaoAlibabaInfo() 阿里巴巴商品信息
 * @method TaobaokeQuery taobaokeQuery() 淘口令解析api接口
 * @method TaobaokeCheckOrder taobaokeCheckOrder() 淘宝客订单号检测接口,检测是否使用了淘客下单
 * @method TelecomQuery telecomQuery() 手机实名认证
 * @method TelecomDetail telecomDetail() 手机实名校验 根据姓名、身份证、手机号码校验是否一致,并返回不一致详情
 * @method TelecomLocation telecomLocation() 手机号码归属地查询
 * @method TelecomIdentify telecomIdentify() 查询手机/固话号码归属地，是否诈骗、营销、广告电话
 * @method TelecomCodes telecomCodes() 根据传入的字符返回标准电码
 * @method TimeLunar timeLunar() 二十四节气
 * @method TimeHoliday timeHoliday() 节假日
 * @method TimeWorld timeWorld() 全球时间查询
 * @method TodayEvent todayEvent() 根据日期查询事件（列表）
 * @method TodayDetail todayDetail() 根据事件id查询详细信息
 * @method TrashIndex trashIndex() 垃圾分类
 * @method TrashVoice trashVoice() 语音识别垃圾分类
 * @method TrashImage trashImage() 图像识别垃圾分类
 * @method TrashSearch trashSearch() 名称识别垃圾分类
 * @method TulingIndex tulingIndex() 图灵机器人
 * @method UnnBatchUcheck unnBatchUcheck() 手机空号检测
 * @method UnnStatus unnStatus() 号码实时查询（基础版）
 * @method Verifybankcard3Query verifybankcard3Query()
 * @method Verifybankcard4Query verifybankcard4Query()
 * @method WeatherCoords weatherCoords() 根据名坐标查询天气
 * @method WeatherIp weatherIp() 根据IP地址查询天气
 * @method WeatherArea weatherArea() 根据名称或者ID查询天气
 * @method WeatherAreaForecast24 weatherAreaForecast24() 根据名称或者ID查询24小时天气预报
 * @method WeatherAreaForecast7 weatherAreaForecast7() 根据名称或者ID查询未来7天指定日期天气预报
 * @method WeatherAreaForecast15 weatherAreaForecast15() 根据名称或者ID查询15天以内天气预报
 * @method WeatherAreaForecast40 weatherAreaForecast40() 根据名称或者ID查询40天以内天气预报
 * @method WeatherAreaHistory weatherAreaHistory() 根据名称或者ID查询历史天气预报
 * @method WeatherScenic weatherScenic() 根据景点名称查询天气
 * @method WeatherZip weatherZip() 根据邮编查询天气
 * @method WeatherAreaId weatherAreaId() 根据地名查询对应的ID
 * @method WeatherQuery weatherQuery()
 * @method WeatherLife weatherLife()
 * @method WeatherWids weatherWids() 查询天气种类列表（可以程序内置，无需每次读取）
 * @method WeatherCityList weatherCityList() 查询当前支持的城市列表及城市ID（可以一次性读取保存）
 * @method WebsiteBeian websiteBeian() 网站备案查询
 * @method WebsiteQq websiteQq() 腾讯域名检测
 * @method WebsiteCheatlink websiteCheatlink() 恶意链接检测
 * @method WebsiteCheatip websiteCheatip() 恶意IP检测
 * @method WebsiteAntispam websiteAntispam() 文本内容审核
 * @method WebsiteAdreview websiteAdreview() 广告法违禁词汇
 * @method WebsiteImgcensor websiteImgcensor() 图片内容审核
 * @method WebsiteSurl websiteSurl() 防封短网址生成
 * @method WebsiteShorturl websiteShorturl() 短网址转换
 * @method WebsiteHttps websiteHttps() HTTPS检测
 * @method WebsiteBaidu websiteBaidu() 百度收录量
 * @method WebsiteSogou websiteSogou() 搜狗收录量
 * @method WebsiteSo websiteSo() 360收录量
 * @method WebsiteIpv6 websiteIpv6() ipv6检测
 * @method WebsiteDomain websiteDomain() 查询域名信息
 * @method WebsiteIp websiteIp() 查询IP地址信息
 * @method WebsiteWabeian websiteWabeian() 网安备案查询
 * @method WebsiteTdk websiteTdk() 网站TDK信息
 * @method WebsiteIcp websiteIcp() ICP域名备案查询
 * @method WebsiteCompany websiteCompany() 主办单位备案查询（实时）
 * @method WebsiteAlexa websiteAlexa() Alexa查询
 * @method WebsiteWhois websiteWhois() whois查询
 * @method WebsiteWhoisReverse websiteWhoisReverse() whois反查
 * @method WebsiteTop websiteTop() 网站排行榜
 * @method WebsiteHtmlpic websiteHtmlpic() 抽取网页图片
 * @method WebsiteHtmltext websiteHtmltext() 获取网页文章/新闻全文内容
 * @method WebsiteUrl2pic websiteUrl2pic() 网址转换为图片及PDF
 * @method WechatChoice wechatChoice() 微信公众号精选文章
 * @method WechatSearch wechatSearch() 微信文章搜索
 * @method WechatLink wechatLink() 微信临时链接转为永久链接
 * @method WechatCheck wechatCheck() 微信域名检测
 * @method WechatDomainCheck wechatDomainCheck() 微信域名检测
 * @method WechatRead wechatRead() 微信文章阅读和点赞数
 * @method WikiIndex wikiIndex() 十万个为什么
 * @method WikiTiku wikiTiku() 百科题库
 * @method WikiRiddle wikiRiddle() 谜语大全
 * @method WikiLantern wikiLantern() 灯谜
 * @method WikiStory wikiStory() 故事大全
 * @method WikiTongue wikiTongue() 绕口令
 * @method WikiDoggerel wikiDoggerel() 顺口溜
 * @method WikiXiehou wikiXiehou() 歇后语
 * @method WikiHotword wikiHotword() 网络热词
 * @method WikiGodreply wikiGodreply() 神回复
 * @method WoolWcheck woolWcheck() 羊毛党检测
 * @method WoolWtag woolWtag() 羊毛党检测标签版
 * @method WordSegment wordSegment() 中文分词
 * @method XinhuaQuery xinhuaQuery() 根据汉字查询相关信息，如拼音、读音、详解、五笔等
 * @method XinhuaBushou xinhuaBushou() 汉字部首列表大全，包含笔画信息
 * @method XinhuaPinyin xinhuaPinyin() 汉字拼音列表大全
 * @method XinhuaQuerybs xinhuaQuerybs() 根据汉字部首,查询符合条件的汉字详细信息
 * @method XinhuaQuerypy xinhuaQuerypy() 根据汉字的拼音，查询相关的汉字信息
 * @method XinhuaQueryid xinhuaQueryid() 根据接口列表返回的汉字id，查询汉字完整信息
 * @method XinhuaResemble xinhuaResemble() 查询词语的同义词、反义词。
 * @method XinhuaConvertPy xinhuaConvertPy() 汉字转拼音
 * @method XnbIndex xnbIndex() 数字BTC,ETH,LTC地址
 */
trait DefaultRequests
{
}
