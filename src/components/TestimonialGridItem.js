import "./TestimonialGridItem.css";

export default function TestimonialGridItem({item}){
    return <>
        <div className="photo"><img src={item.imageUrl} title={item.name} alt={item.name} /></div>
        <div className="details">
            <div className="profile-age">{item.name} ({item.age}) </div>
            <div className="profile-location">{item.location}</div>
            <hr />
            <div className="profile-comments">{item.comments}</div>
        </div>
        </>

}