<?php

namespace think\api\request\douyin;

use think\api\Request;

class BoardStar extends Request
{
}

class BoardHot extends Request
{
}

class BoardLive extends Request
{
}

class BoardVideo extends Request
{
}

class BoardGood extends Request
{
}

class BoardRecommend extends Request
{
}

class BoardTag extends Request
{
}

/**
 * @method $this withRoomId($value)
 */
class LiveroomInfo extends Request
{
}

/**
 * @method $this withRoomId($value)
 */
class LiveroomChat extends Request
{
}

/**
 * @method $this withRoomId($value)
 */
class LiveroomPromotion extends Request
{
}

/**
 * @method $this withRoomId($value)
 */
class LiveroomStatus extends Request
{
}

/**
 * @method $this withRoomId($value)
 */
class LiveroomAudience extends Request
{
}

class LiveroomFeed extends Request
{
}

/**
 * @method $this withKeyword($value)
 * @method $this withCursor($value)
 */
class SearchUser extends Request
{
}

/**
 * @method $this withKeyword($value)
 * @method $this withCursor($value)
 * @method $this withSort($value)
 * @method $this withCtime($value)
 */
class SearchVideo extends Request
{
}

/**
 * @method $this withKeyword($value)
 * @method $this withCursor($value)
 */
class SearchTopic extends Request
{
}

/**
 * @method $this withKeyword($value)
 * @method $this withCursor($value)
 */
class SearchPoi extends Request
{
}

/**
 * @method $this withKeyword($value)
 * @method $this withCursor($value)
 */
class SearchMusic extends Request
{
}

/**
 * @method $this withKeyword($value)
 * @method $this withCursor($value)
 */
class SearchLive extends Request
{
}

/**
 * @method $this withKeyword($value)
 * @method $this withCursor($value)
 */
class SearchGood extends Request
{
}

/**
 * @method $this withKeyword($value)
 * @method $this withCursor($value)
 */
class SearchIndex extends Request
{
}

/**
 * @method $this withChid($value)
 */
class TopicDetail extends Request
{
}

/**
 * @method $this withChid($value)
 * @method $this withCursor($value)
 */
class TopicVideo extends Request
{
}

/**
 * @method $this withUid($value)
 */
class UserInfo extends Request
{
}

/**
 * @method $this withUid($value)
 * @method $this withCursor($value)
 */
class UserVideo extends Request
{
}

/**
 * @method $this withUid($value)
 * @method $this withCursor($value)
 */
class UserFollower extends Request
{
}

/**
 * @method $this withUid($value)
 */
class UserLive extends Request
{
}

/**
 * @method $this withUid($value)
 * @method $this withCursor($value)
 */
class UserPromotion extends Request
{
}

/**
 * @method $this withUid($value)
 * @method $this withCursor($value)
 */
class UserFollowing extends Request
{
}

/**
 * @method $this withUid($value)
 * @method $this withCursor($value)
 */
class UserFavourite extends Request
{
}

/**
 * @method $this withAwemeId($value)
 */
class VideoDetail extends Request
{
}

/**
 * @method $this withAwemeId($value)
 * @method $this withCursor($value)
 */
class VideoComment extends Request
{
}

/**
 * @method $this withAwemeId($value)
 */
class VideoPromotion extends Request
{
}

/**
 * @method $this withAwemeId($value)
 * @method $this withCid($value)
 * @method $this withCursor($value)
 */
class VideoReply extends Request
{
}

/**
 * @method $this withCursor($value)
 */
class VideoFeed extends Request
{
}

/**
 * @method $this withPid($value)
 */
class VideoPromotionDetail extends Request
{
    public $uri = 'video/promotion_detail';
}

/**
 * @method $this withPid($value)
 */
class VideoPromotionVideo extends Request
{
    public $uri = 'video/promotion_video';
}

/**
 * @method BoardStar boardStar() 明星榜
 * @method BoardHot boardHot() 热点榜
 * @method BoardLive boardLive() 直播榜
 * @method BoardVideo boardVideo() 最热视频榜
 * @method BoardGood boardGood() 人气好物榜
 * @method BoardRecommend boardRecommend() 首页视频推荐
 * @method BoardTag boardTag() 热门话题推荐
 * @method LiveroomInfo liveroomInfo() 直播间信息
 * @method LiveroomChat liveroomChat() 直播 送礼、关注、点赞、弹幕
 * @method LiveroomPromotion liveroomPromotion() 直播带货商品列表
 * @method LiveroomStatus liveroomStatus() 直播间开播查询
 * @method LiveroomAudience liveroomAudience() 直播间在线观众
 * @method LiveroomFeed liveroomFeed() 直播间随机推荐
 * @method SearchUser searchUser() 关键词搜索用户
 * @method SearchVideo searchVideo() 关键词搜索视频
 * @method SearchTopic searchTopic() 关键词搜索话题
 * @method SearchPoi searchPoi() 关键词搜索地点
 * @method SearchMusic searchMusic() 关键词搜索音乐
 * @method SearchLive searchLive() 关键词搜索直播
 * @method SearchGood searchGood() 关键词搜索商品
 * @method SearchIndex searchIndex() 关键词综合搜索
 * @method TopicDetail topicDetail() 话题详情
 * @method TopicVideo topicVideo() 话题视频列表
 * @method UserInfo userInfo() 抖音用户信息
 * @method UserVideo userVideo() 抖音用户视频列表
 * @method UserFollower userFollower() 抖音用户粉丝列表
 * @method UserLive userLive() 抖音用户直播信息
 * @method UserPromotion userPromotion() 抖音用户商品橱窗
 * @method UserFollowing userFollowing() 抖音用户关注列表
 * @method UserFavourite userFavourite() 抖音用户收藏列表
 * @method VideoDetail videoDetail() 视频详情
 * @method VideoComment videoComment() 视频评论列表
 * @method VideoPromotion videoPromotion() 视频带货商品列表
 * @method VideoReply videoReply() 视频评论回复列表
 * @method VideoFeed videoFeed() 带货视频随机推荐
 * @method VideoPromotionDetail videoPromotionDetail() 带货商品详情
 * @method VideoPromotionVideo videoPromotionVideo() 带同款商品视频列表
 */
trait DouyinRequests
{
}
