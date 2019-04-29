StreamLabs Backend
========================
**Test url: [https://streamlabs-od.herokuapp.com/][1]**

**Preparation:**
    
Set next values to env
    
   `client_id=<twitch client id>`
   
   `client_secret=<twitch secret>`
   
   `redirect_url=<twitch redirect url after oAuth login>`
   
   `webhook_url=<endpoint for webhhoks>`
   
   `websocket_url=<url for socket io totifier>`
   

Install:

  `composer install`
  
  `cd socket && npm install`
  

What's inside?
--------------

Symfony 3.4 + Socket.IO Server

Symfony as backend handles authentication and WebHooks endpoint.

In general events handling looks this way:

    Twitch WebHook > Symfony WebHook Handler > Socket.io -> Client 

> Socket.IO is selected because it's simple and quick, unlike php implementation
  

Questions?
--------------

**1. How would you deploy the above on AWS?**

There's simple app so we don't need much resources. Let's pick t2.small instances(and fingers-crossed that composer will do it =).

As deploying tool I would chose ElasticBeanstalk or Docker.

I will not draw diagram so general flow is:
    
   * EC2 t2.small instances with loadbalancer fro Symfony App
   * EC2 t2.micro instance for Socket.io app (we don't need upscale here before reach a lot of traffic)

**2.Where do you see bottlenecks in your proposed architecture and how would you approach scaling this app starting from 100 reqs/day to 900MM reqs/day over 6 months?**

Let's start from optimizing code for WebHooks. Currently this was rough, and not done well code.
We need to use Redis or since we are stopped at AWS, ElastiCache. We need to save WebHook events for given streamer to not process them multiple times.
This case is important if a lot of users watch same streamer. 

In order to adding cache - websocket system should be optimized. 
Add rooms for specific streamer to get all events in one bucket, and notify only connected to room users.

Add FIFO worker - to prevent servers overload. RabbitMQ or in our case SQS + worker will work on processing callback data, Cache I/O.
Other worker will pass Events to websocket.

Final architecture will be kind:

  * EC2 instances cloud - as frontend + authorization.
  * EC2 workers cloud - to get WebHooks data put in to ElasticCache and pass it into SQS.
  * ElastiCache with data from twitch and prepared results
  * SQS for callback data
  * EC2 workers cloud - to pass prepared and cached data to Socket.IO
  * SQS for calls to Socket.IO
  * EC2 Socket.IO cloud to handle all connected users
  
With this flow - working with servers analytics we can set correct autoscale rules and handle huge amount of connections.
 
> I can draw diagram if something of this was not clear

And there always some items that can be optimised in code or architecture. Some languages can handle tasks much faster, serverless stack can handle most if the issues.


Thank you for your time!
 
[1]:  https://streamlabs-od.herokuapp.com