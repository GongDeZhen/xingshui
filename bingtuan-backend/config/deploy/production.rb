domain = case ENV['domain']
         when 'bt01' then '123.57.174.107'
         when 'bt02' then '101.200.127.182'
         else
          nil
         end

if domain.nil?
  puts "Please specify a domain name to deploy"
  exit
end
set :domain, domain
set :branch, 'master'
set :deploy_to, '/home/www/app/bingtuan-production'

set :user, 'root'
set :port, '51022'